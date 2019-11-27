<?php


namespace App\Utils;


use App\Entity\SyncRecord;
use ArtemBro\TransferWiseApiBundle\Client\Client;
use ArtemBro\TransferWiseApiBundle\Service\TransferWiseApiService;

trait TransferWiseClientTrait
{
    /**
     * @param TransferWiseApiService $transferWiseApiService
     * @param SyncRecord $syncRecord
     * @return Client
     */
    public function getTAClientForRecord(TransferWiseApiService $transferWiseApiService, SyncRecord $syncRecord)
    {
        return $transferWiseApiService->getClient($syncRecord->getTransferWiseApiToken(),
            $syncRecord->getTransferWiseApiEnvironment() ? TransferWiseApiService::API_ENVIRONMENT_PROD : TransferWiseApiService::API_ENVIRONMENT_DEV);
    }

}