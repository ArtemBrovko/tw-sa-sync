<?php
/**
 * @author Artem Brovko <brovko.artem@gmail.com>
 * @copyright 2019 Artem Brovko
 */

namespace App\Service;


use App\Entity\CachedSyncObject;
use App\Entity\Job;
use App\Entity\SyncRecord;
use App\Sync\SyncResult;
use ArtemBro\SmartAccountsApiBundle\Model\Client;
use ArtemBro\SmartAccountsApiBundle\Model\Enum\AccountType;
use ArtemBro\SmartAccountsApiBundle\Model\Payment;
use ArtemBro\SmartAccountsApiBundle\Service\SmartAccountsApiService;
use ArtemBro\TransferWiseApiBundle\Service\TransferWiseApiService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class SyncService
{
    /**
     * @var TransferWiseApiService
     */
    private $transferWiseService;

    /**
     * @var SmartAccountsApiService
     */
    private $smartAccountsApiService;

    /**
     * @var SmartAccountsApiService
     */
    private $em;

    /**
     * SyncService constructor.
     *
     * @param TransferWiseApiService $transferWiseService
     * @param SmartAccountsApiService $smartAccountsApiService
     * @param EntityManagerInterface $em
     */
    public function __construct(TransferWiseApiService $transferWiseService,
                                SmartAccountsApiService $smartAccountsApiService,
                                EntityManagerInterface $em)
    {
        $this->transferWiseService = $transferWiseService;
        $this->smartAccountsApiService = $smartAccountsApiService;
        $this->em = $em;
    }

    /**
     * @param SyncRecord $syncRecord
     *
     * @return SyncResult
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sync(SyncRecord $syncRecord)
    {
        $job = $this->startJob($syncRecord);

        $result = new SyncResult();

        $endDate = new \DateTime();
        $startDate = clone $endDate;
        $startDate->sub(new \DateInterval('P1M2D'));

        $dateFormat = 'Y-m-d';

        $transferWiseClient = $this->transferWiseService->getClientForRecord($syncRecord);
        $smartAccountsClient = $this->smartAccountsApiService->getClient($syncRecord->getSmartAccountsApiKeyPublic(), $syncRecord->getSmartAccountsApiKeyPrivate());

        try {
            $saFallbackAccountName = 'Transferwise (holding)';
            $saFallbackAccount = $this->getSAAccountByName($smartAccountsClient, $saFallbackAccountName);

            $currencies = $transferWiseClient->getAvailableCurrencies();
            $profiles = $transferWiseClient->getProfiles();

            foreach ($currencies as $currency) {
                if ($currency->hasBankDetails) {
                    foreach ($profiles as $profile) {
                        if ($profile->type === TransferWiseApiService::PROFILE_BUSINESS) {
                            $profileId = $profile->id;
                            $currencyCode = $currency->code;

                            $borderlessAccounts = $transferWiseClient->getBorderlessAccounts($profileId);

//                            print_r($borderlessAccounts);

                            foreach ($borderlessAccounts as $borderlessAccount) {
                                $accountDetails = $transferWiseClient->getBorderlessAccount($borderlessAccount->id, $currencyCode, $startDate, $endDate);

//                                print_r($accountDetails);

                                foreach ($accountDetails->transactions as $transaction) {
                                    if ($this->shouldProcessTransaction($transaction)) {

                                        $twRefNumber = $transaction->referenceNumber;

                                        if ($this->isSynced($syncRecord, $twRefNumber)) {
                                            $result->addSkipped($twRefNumber, $transaction);
                                            $job->increaseSkipped();
                                            continue;
                                        }

                                        $senderName = $transaction->details->senderName;

                                        $page = 1;
                                        $clientId = null;
                                        do {
                                            $saCandidateClients = json_decode($smartAccountsClient->getClients($page++));
                                            if (property_exists($saCandidateClients, 'clients')) {
                                                foreach ($saCandidateClients->clients as $client) {
                                                    if (property_exists($client, 'name') && $client->name === $senderName) {
                                                        $clientId = $client->id;
                                                    }
                                                }
                                            }
                                        } while ($saCandidateClients->hasMoreEntries);


                                        if (!isset($clientId)) {
                                            $saClient = new Client();
                                            $saClient->setName($senderName);
                                            if (!empty($transaction->details->senderAccount)) {
                                                $saClient->setBankAccount($transaction->details->senderAccount);
                                            }
                                            $saClient->setVatPc(0);

                                            $response = json_decode($smartAccountsClient->sendClient($saClient));

                                            $clientId = $response->clientId;
                                        }

                                        $payment = new Payment();
                                        $payment->setDate(new \DateTime($transaction->date));
                                        $payment->setClientId($clientId);
                                        $payment->setAccountType(AccountType::BANK);
                                        $payment->setAccountName($saFallbackAccountName);
                                        $payment->setAmount($transaction->amount->value);
                                        $payment->setCurrency($transaction->amount->currency);
                                        $payment->setExchangeRate(1);
                                        $payment->setComment($transaction->details->description);
                                        $payment->setNumber($twRefNumber);
                                        $payment->setExtras([array(
                                            'price'       => $transaction->amount->value,
                                            'quantity'    => 1,
                                            'description' => $transaction->details->description,
                                            'account'     => $saFallbackAccount->account,
                                        )]);

                                        $response = $smartAccountsClient->sendPurchase($payment);

                                        if ($response->getStatusCode() === 200) {
                                            $jsonResponse = json_decode($response->getBody()->getContents());

                                            $job->increaseAdded();
                                            $result->addImported($twRefNumber, $transaction);
                                            $this->saveSyncedRecord($job, $twRefNumber, $jsonResponse->paymentId);
                                        } else {
                                            $error = json_decode($response->getBody()->getContents());
                                            if ($error && $error->errors) {
                                                foreach ($error->errors as $currentError) {
                                                    $result->addError($transaction->referenceNumber, $currentError->message);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $job->addToLog($e->getMessage() . "\n" . $e->getTraceAsString());
            throw $e;
        } finally {
            $this->finishJob($job);
        }

        return $result;
    }

    /**
     * @param SyncRecord $syncRecord
     *
     * @return Job
     * @throws \Exception
     */
    private function startJob(SyncRecord $syncRecord)
    {
        $job = new Job();
        $job->setStarted(new \DateTime());
        $job->setSyncRecord($syncRecord);

        $this->em->persist($job);
        $this->em->flush();

        return $job;
    }

    /**
     * @param \ArtemBro\SmartAccountsApiBundle\Client\Client $smartAccountsClient
     * @param $name
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getSAAccountByName(\ArtemBro\SmartAccountsApiBundle\Client\Client $smartAccountsClient, $name)
    {
        static $accounts = null;

        if (!isset($accounts)) {
            $accounts = json_decode($smartAccountsClient->getBankAccounts());
        }

        foreach ($accounts->bankAccounts as $account) {
            if (property_exists($account, 'name') && $account->name === $name) {
                return $account;
            }
        }

        throw new \Exception('No account with name "' . $name . '"');
    }

    /**
     * @param Job $job
     *
     * @return Job
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function finishJob(Job $job)
    {
        $job->setFinished(new \DateTime());

        $this->em->persist($job);
        $this->em->flush();

        return $job;
    }

    /**
     * @param \ArtemBro\SmartAccountsApiBundle\Client\Client $smartAccountsClient
     * @param $iban
     *
     * @return |null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getSAAccountByIBAN(\ArtemBro\SmartAccountsApiBundle\Client\Client $smartAccountsClient, $iban)
    {
        static $accounts = null;

        if (!isset($accounts)) {
            $accounts = json_decode($smartAccountsClient->getBankAccounts());
        }

        foreach ($accounts as $account) {
            if ($account->iban == $iban) {
                return $account;
            }
        }

        return null;
    }

    private function isSynced(SyncRecord $syncRecord, $twRefNumber)
    {
        static $synced;

        if (!isset($synced)) {
            $synced = [];
        }

        if (!isset($synced[$syncRecord->getId()])) {
            $synced[$syncRecord->getId()] = $this->em->getRepository(CachedSyncObject::class)->findBySyncRecord($syncRecord);
        }

        return in_array($twRefNumber, $synced[$syncRecord->getId()]);
    }

    private function saveSyncedRecord(Job $job, $twReference, $saReference)
    {
        $cachedSyncObject = new CachedSyncObject();
        $cachedSyncObject->setJob($job)
            ->setTransferWiseId($twReference)
            ->setSmartAccountsId($saReference);

        $this->em->persist($cachedSyncObject);
        $this->em->flush();
    }

    private function shouldProcessTransaction($transaction)
    {
        return $transaction->type === TransferWiseApiService::TRANSACTION_TYPE_CREDIT &&
            in_array($transaction->details->type, [
                TransferWiseApiService::TRANSACTION_DETAILS_TYPE_CARD,
                TransferWiseApiService::TRANSACTION_DETAILS_TYPE_DEPOSIT,
                TransferWiseApiService::TRANSACTION_DETAILS_TYPE_TRANSFER,
            ]);
    }
}