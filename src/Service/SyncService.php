<?php
/**
 * @author Artem Brovko <artem.brovko@modera.net>
 * @copyright 2019 Modera Foundation
 */

namespace App\Service;

use App\Model\SmartAccounts\Client;
use App\Model\SmartAccounts\Enum\AccountType;
use App\Model\SmartAccounts\Payment;

class SyncService
{
    /**
     * @var TransferWiseService
     */
    private $transferWiseService;

    /**
     * @var SmartAccountsService
     */
    private $smartAccountsService;

    public function __construct(TransferWiseService $transferWiseService, SmartAccountsService $smartAccountsService)
    {
        $this->transferWiseService = $transferWiseService;
        $this->smartAccountsService = $smartAccountsService;
    }

    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sync()
    {
        $saAccountName = 'Transferwise (holding)';
        $targetAccount = $this->getSAAccountByName($saAccountName);

        $cacheFile = __DIR__ . '/synced.txt';

        if (file_exists($cacheFile)) {
            $synced = explode(';', file_get_contents($cacheFile));
        } else {
            $synced = [];
        }

        $imported = array();
        $skipped = array();
        $errors = array();

        $endDate = new \DateTime();
        $startDate = clone $endDate;
        $startDate->sub(new \DateInterval('P1M'));

        $dateFormat = 'Y-m-d';

        $transferList = $this->transferWiseService->getTransfersList($startDate->format($dateFormat), $endDate->format($dateFormat), TransferWiseService::TRANSFER_STATUS_OUTGOING_PAYMENT_SENT);

        foreach ($transferList as $item) {
            if (in_array($item->id, $synced)) {
                $skipped[$item->id] = $item;
                continue;
            }

//            print_r($item);

            $account = $this->transferWiseService->getAccount($item->targetAccount);
            $accountName = $account->accountHolderName;

            $page = 1;
            $clientId = null;
            do {
                $saCandidateClients = json_decode($this->smartAccountsService->getClients($page++));
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

                $response = json_decode($this->smartAccountsService->sendClient($saClient));

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

            $response = $this->smartAccountsService->sendPurchase($payment);

            if ($response->getStatusCode() === 200) {
                $synced[] = $item->id;
                file_put_contents($cacheFile, implode(';', $synced));
                $imported[$item->id] = $item;
            } else {
                $error = json_decode($response->getBody()->getContents());
                if ($error && $error->errors) {
                    $errors[$item->id] = [];
                    foreach ($error->errors as $currentError) {
                        $errors[$item->id][] = $currentError->message;
                        print_r($payment);
                    }
                }
            }
        }

        return array(
            'imported' => $imported,
            'errors'   => $errors,
            'skipped'  => $skipped
        );
    }

    private function getSAAccountByIBAN($iban)
    {
        static $accounts = null;

        if (!isset($accounts)) {
            $accounts = json_decode($this->smartAccountsService->getBankAccounts());
        }

        foreach ($accounts as $account) {
            if ($account->iban == $iban) {
                return $account;
            }
        }

        return null;
    }

    /**
     * @param $name
     *
     * @return mixed
     * @throws \Exception
     */
    private function getSAAccountByName($name)
    {
        static $accounts = null;

        if (!isset($accounts)) {
            $accounts = json_decode($this->smartAccountsService->getBankAccounts());
        }

        foreach ($accounts->bankAccounts as $account) {
            if (property_exists($account, 'name') && $account->name === $name) {
                return $account;
            }
        }

        throw new \Exception('No account with name "' . $name . '"');
    }
}