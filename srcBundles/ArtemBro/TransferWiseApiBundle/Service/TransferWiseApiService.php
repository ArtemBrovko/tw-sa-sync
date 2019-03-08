<?php
/**
 * @author Artem Brovko <brovko.artem@gmail.com>
 * @copyright 2019 Artem Brovko
 */

namespace ArtemBro\TransferWiseApiBundle\Service;


use App\Entity\SyncRecord;
use ArtemBro\TransferWiseApiBundle\Client\Client;

class TransferWiseApiService
{
    const PROFILE_PERSONAL = 'personal';
    const PROFILE_BUSINESS = 'business';

    const API_ENVIRONMENT_DEV = 'dev';
    const API_ENVIRONMENT_PROD = 'prod';

    const TRANSFER_STATUS_INCOMING_PAYMENT_WAITING = 'incoming_payment_waiting';
    const TRANSFER_STATUS_PROCESSING = 'processing';
    const TRANSFER_STATUS_FUNDS_CONVERTED = 'funds_converted';
    const TRANSFER_STATUS_OUTGOING_PAYMENT_SENT = 'outgoing_payment_sent';
    const TRANSFER_STATUS_BOUNCED_BACK = 'bounced_back';
    const TRANSFER_STATUS_FUNDS_REFUNDED = 'funds_refunded';

    const TRANSACTION_TYPE_CREDIT = 'CREDIT';
    const TRANSACTION_TYPE_DEBIT = 'DEBIT';

    const TRANSACTION_DETAILS_TYPE_CARD = 'CARD';
    const TRANSACTION_DETAILS_TYPE_CONVERSION = 'CONVERSION';
    const TRANSACTION_DETAILS_TYPE_DEPOSIT = 'DEPOSIT';
    const TRANSACTION_DETAILS_TYPE_TRANSFER = 'TRANSFER';
    const TRANSACTION_DETAILS_TYPE_MONEY_ADDED = 'MONEY_ADDED';

    /**
     * @param SyncRecord $syncRecord
     *
     * @return Client
     */
    public function getClientForRecord(SyncRecord $syncRecord)
    {
        return $this->getClient($syncRecord->getTransferWiseApiToken(),
            $syncRecord->getTransferWiseApiEnvironment() ? TransferWiseApiService::API_ENVIRONMENT_PROD : TransferWiseApiService::API_ENVIRONMENT_DEV);
    }

    /**
     * @param $apiKey
     * @param string $env
     *
     * @return Client
     */
    public function getClient($apiKey, $env = TransferWiseApiService::API_ENVIRONMENT_DEV)
    {
        return new Client($apiKey, $env);
    }
}