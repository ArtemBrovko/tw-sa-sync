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
     * @var boolean
     */
    private $dryRun = true;

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
     * @throws \DomainException
     */
    public function sync(SyncRecord $syncRecord)
    {
        $job = $this->startJob($syncRecord);

        $result = new SyncResult();

        $endDate = new \DateTime();
        $startDate = clone $endDate;
        $startDate->sub(new \DateInterval('P1M2D'));

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

                            foreach ($borderlessAccounts as $borderlessAccount) {
                                $borderlessAccountId = $borderlessAccount->id;
                                $accountDetails = $transferWiseClient->getBorderlessAccount($borderlessAccountId, $currencyCode, $startDate, $endDate);

                                foreach ($accountDetails->transactions as $transaction) {
                                    $twRefNumber = $transaction->referenceNumber;

                                    try {
                                        if ($this->shouldProcessTransaction($transaction)) {

                                            if ($this->isSynced($syncRecord, $twRefNumber)) {
                                                $result->addSkipped($transaction);
                                                $job->increaseSkipped();
                                                continue;
                                            }

                                            $clientId = $this->getOrCreateSAAccount(
                                                $smartAccountsClient,
                                                $this->resolveSenderName($transaction->details),
                                                $this->resolveAccountNumber($transaction->details));

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

                                            if (!$this->isDryRun()) {
                                                $response = $smartAccountsClient->sendPurchase($payment);

                                                if ($response->getStatusCode() === 200) {
                                                    $jsonResponse = json_decode($response->getBody()->getContents());

                                                    $job->increaseAdded();
                                                    $result->addImported($transaction);
                                                    $this->saveSyncedRecord($job,
                                                        $jsonResponse->paymentId,
                                                        $twRefNumber,
                                                        $profileId,
                                                        $borderlessAccountId,
                                                        $transaction);
                                                } else {
                                                    $error = json_decode($response->getBody()->getContents());
                                                    if ($error && $error->errors) {
                                                        foreach ($error->errors as $currentError) {
                                                            $result->addError($transaction->referenceNumber, $currentError->message);
                                                        }
                                                    }
                                                }
                                            } else {
                                                $job->increaseAdded();
                                                $result->addImported($transaction);
                                            }
                                        } else {
                                            $result->addWontProcess($transaction);
                                        }
                                    } catch (\Exception $e) {
                                        $result->addError($transaction->referenceNumber, $e->getMessage());
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

        if (!$this->isDryRun()) {
            $this->em->persist($job);
            $this->em->flush();
        }

        return $job;
    }

    /**
     * @return bool
     */
    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    /**
     * @param bool $dryRun
     */
    public function setDryRun(bool $dryRun): void
    {
        $this->dryRun = $dryRun;
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

        if (is_object($accounts)) {
            foreach ($accounts->bankAccounts as $account) {
                if (property_exists($account, 'name') && $account->name === $name) {
                    return $account;
                }
            }
        }

        throw new \Exception('No account with name "' . $name . '"');
    }

    private function shouldProcessTransaction($transaction)
    {
        return in_array($transaction->type, [
                TransferWiseApiService::TRANSACTION_TYPE_CREDIT,
                TransferWiseApiService::TRANSACTION_TYPE_DEBIT,
            ]) && in_array($transaction->details->type, [
                TransferWiseApiService::TRANSACTION_DETAILS_TYPE_CARD,
                TransferWiseApiService::TRANSACTION_DETAILS_TYPE_DEPOSIT,
                TransferWiseApiService::TRANSACTION_DETAILS_TYPE_TRANSFER,
            ]);
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

    private function getOrCreateSAAccount($smartAccountsClient, $senderName, $senderAccount)
    {
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
            if (!empty($senderAccount)) {
                $saClient->setBankAccount($senderAccount);
            }
            $saClient->setVatPc(0);

            if (!$this->isDryRun()) {
                $response = json_decode($smartAccountsClient->sendClient($saClient));

                if (is_object($response)) {
                    $clientId = $response->clientId;
                } else {
                    throw new \Exception("Cannot create SmartAccounts client");
                }
            } else {
                $clientId = "CLIENT-TO-BE-CREATED";
            }
        }

        return $clientId;
    }

    /**
     * Returns applicable client name from transaction details
     *
     * @param $transactionDetails
     *
     * @return string
     */
    private function resolveSenderName($transactionDetails)
    {
        switch ($transactionDetails->type) {
            case TransferWiseApiService::TRANSACTION_DETAILS_TYPE_CARD:
                return $transactionDetails->merchant->name;

            case TransferWiseApiService::TRANSACTION_DETAILS_TYPE_TRANSFER:
                return $transactionDetails->recipient->name;

            default:
                return $transactionDetails->senderName;
        }
    }

    /**
     * @param $transactionDetails
     *
     * @return null
     */
    private function resolveAccountNumber($transactionDetails)
    {
        switch ($transactionDetails->type) {
            case TransferWiseApiService::TRANSACTION_DETAILS_TYPE_CARD:
                return null;

            case TransferWiseApiService::TRANSACTION_DETAILS_TYPE_TRANSFER:
                return $transactionDetails->recipient->bankAccount;

            default:
                return $transactionDetails->senderAccount;
        }
    }

    private function saveSyncedRecord(Job $job, $saReference, $twReference, $twProfileId, $twBorderlessAccountId, $twTransaction)
    {
        $cachedSyncObject = new CachedSyncObject();
        $cachedSyncObject->setJob($job)
            ->setSmartAccountsId($saReference)
            ->setTransferWiseId($twReference)
            ->setTwProfileId($twProfileId)
            ->setTwBorderlessAccountId($twBorderlessAccountId)
            ->setTwTransaction(json_encode($twTransaction));

        $this->em->persist($cachedSyncObject);
        $this->em->flush();
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

        if (!$this->isDryRun()) {
            $this->em->persist($job);
            $this->em->flush();
        }

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
}