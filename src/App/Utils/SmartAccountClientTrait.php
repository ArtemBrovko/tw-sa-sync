<?php


namespace App\Utils;


use App\Entity\SyncRecord;
use ArtemBro\SmartAccountsApiBundle\Client\Client;
use ArtemBro\SmartAccountsApiBundle\Service\SmartAccountsApiService;

trait SmartAccountClientTrait
{
    /**
     * @param SmartAccountsApiService $smartAccountsApiService
     * @param SyncRecord $syncRecord
     * @return Client
     */
    public function getSAClientForRecord(SmartAccountsApiService $smartAccountsApiService, SyncRecord $syncRecord)
    {
        return $smartAccountsApiService->getClient($syncRecord->getSmartAccountsApiKeyPublic(), $syncRecord->getSmartAccountsApiKeyPrivate());
    }

}