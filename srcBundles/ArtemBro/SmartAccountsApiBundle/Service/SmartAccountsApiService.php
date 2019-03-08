<?php

namespace ArtemBro\SmartAccountsApiBundle\Service;

use App\Entity\SyncRecord;
use ArtemBro\SmartAccountsApiBundle\Client\Client;

/**
 * @author Artem Brovko <brovko.artem@gmail.com>
 * @copyright 2019 Artem Brovko
 */
class SmartAccountsApiService
{

    /**
     * @param SyncRecord $syncRecord
     *
     * @return Client
     */
    public function getClientForRecord(SyncRecord $syncRecord)
    {
        return $this->getClient($syncRecord->getSmartAccountsApiKeyPublic(), $syncRecord->getSmartAccountsApiKeyPrivate());
    }

    public function getClient($apiKeyPublic, $apiKeyPrivate)
    {
        return new Client($apiKeyPublic, $apiKeyPrivate);
    }
}