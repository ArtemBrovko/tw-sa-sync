<?php
/**
 * @author Artem Brovko <artem.brovko@modera.net>
 * @copyright 2019 Modera Foundation
 */

namespace ArtemBro\TransferWiseApiBundle\Service;


use ArtemBro\TransferWiseApiBundle\Client\Client;

class TransferWiseApiService
{
    const PERSONAL_ACCOUNT_TYPE_NAME = 'personal';

    const TRANSFER_STATUS_INCOMING_PAYMENT_WAITING = 'incoming_payment_waiting';
    const TRANSFER_STATUS_PROCESSING = 'processing';
    const TRANSFER_STATUS_FUNDS_CONVERTED = 'funds_converted';
    const TRANSFER_STATUS_OUTGOING_PAYMENT_SENT = 'outgoing_payment_sent';
    const TRANSFER_STATUS_BOUNCED_BACK = 'bounced_back';
    const TRANSFER_STATUS_FUNDS_REFUNDED = 'funds_refunded';

    public function getClient($apiKey)
    {
        return new Client($apiKey);
    }

    private function getAPIToken()
    {
        return '5a7904d4-c14d-4095-b1e4-12000e20215b';
    }
}

