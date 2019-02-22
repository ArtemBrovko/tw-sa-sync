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
    const PERSONAL_ACCOUNT_TYPE_NAME = 'personal';

    const API_ENVIRONMENT_DEV = 'dev';
    const API_ENVIRONMENT_PROD = 'prod';

    const TRANSFER_STATUS_INCOMING_PAYMENT_WAITING = 'incoming_payment_waiting';
    const TRANSFER_STATUS_PROCESSING = 'processing';
    const TRANSFER_STATUS_FUNDS_CONVERTED = 'funds_converted';
    const TRANSFER_STATUS_OUTGOING_PAYMENT_SENT = 'outgoing_payment_sent';
    const TRANSFER_STATUS_BOUNCED_BACK = 'bounced_back';
    const TRANSFER_STATUS_FUNDS_REFUNDED = 'funds_refunded';

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
}