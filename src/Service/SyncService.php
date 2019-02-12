<?php
/**
 * @author Artem Brovko <artem.brovko@modera.net>
 * @copyright 2019 Modera Foundation
 */

namespace App\Service;


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
        $job = $this->startJob();

        $cacheFile = __DIR__ . '/synced.txt';

        if (file_exists($cacheFile)) {
            $synced = explode(';', file_get_contents($cacheFile));
        } else {
            $synced = [];
        }

        $result = new SyncResult();

        $endDate = new \DateTime();
        $startDate = clone $endDate;
        $startDate->sub(new \DateInterval('P1M'));

        $dateFormat = 'Y-m-d';

        $transferWiseClient = $this->transferWiseService->getClient($syncRecord->getTransferWiseApiToken());
        $smartAccountsClient = $this->smartAccountsApiService->getClient($syncRecord->getSmartAccountsApiKeyPublic(), $syncRecord->getSmartAccountsApiKeyPrivate());

        try {
            $saAccountName = 'Transferwise (holding)';
            $targetAccount = $this->getSAAccountByName($smartAccountsClient, $saAccountName);

            $transferList = $transferWiseClient->getTransfersList($startDate->format($dateFormat), $endDate->format($dateFormat), TransferWiseApiService::TRANSFER_STATUS_OUTGOING_PAYMENT_SENT);

            foreach ($transferList as $item) {
                if (in_array($item->id, $synced)) {
                    $result->addSkipped($item->id, $item);
                    continue;
                }

                $account = $transferWiseClient->getAccount($item->targetAccount);
                $accountName = $account->accountHolderName;

                $page = 1;
                $clientId = null;
                do {
                    $saCandidateClients = json_decode($smartAccountsClient->getClients($page++));
                    if (property_exists($saCandidateClients, 'clients')) {
                        foreach ($saCandidateClients->clients as $client) {
                            if (property_exists($client, 'name') && $client->name === $accountName) {
                                $clientId = $client->id;
                            }
                        }
                    }
                } while ($saCandidateClients->hasMoreEntries);

                if (!isset($clientId)) {
                    $saClient = new Client();
                    $saClient->setName($accountName);
                    $saClient->setVatPc(0);

                    $response = json_decode($smartAccountsClient->sendClient($saClient));

                    $clientId = $response->clientId;
                }

                $payment = new Payment();
                $payment->setDate((new \DateTime($item->created))->format('d.m.Y'));
                $payment->setClientId($clientId);
                $payment->setAccountType(AccountType::BANK);
                $payment->setAccountName($saAccountName);
                $payment->setAmount($item->sourceValue);
                $payment->setCurrency($item->sourceCurrency);
                $payment->setExchangeRate(1);
                $payment->setComment($item->id);
                $payment->setExtras([array(
                    'price'       => $item->sourceValue,
                    'quantity'    => 1,
                    'description' => 'Main purchase',
                    'account'     => $targetAccount->account,
                )]);

                $response = $smartAccountsClient->sendPurchase($payment);

                if ($response->getStatusCode() === 200) {
                    $synced[] = $item->id;
                    file_put_contents($cacheFile, implode(';', $synced));
                    $result->addImported($item->id, $item);
                } else {
                    $error = json_decode($response->getBody()->getContents());
                    if ($error && $error->errors) {
                        foreach ($error->errors as $currentError) {
                            $result->addError($item->id, $currentError->message);
                            print_r($payment);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $job->addToLog(print_r($e, true));
            $this->finishJob($job);

            throw $e;
        }

        return $result;
    }

    /**
     * @return Job
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function startJob()
    {
        $job = new Job();
        $job->setStarted(new \DateTime());

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

    private function isSynced($id)
    {

    }

    private function setIsSynced($job, $recordId)
    {

    }
}