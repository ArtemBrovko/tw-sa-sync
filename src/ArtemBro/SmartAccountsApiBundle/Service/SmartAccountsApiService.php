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
     * @param $apiKeyPublic
     * @param $apiKeyPrivate
     * @return Client
     */
    public function getClient($apiKeyPublic, $apiKeyPrivate)
    {
        return new Client($apiKeyPublic, $apiKeyPrivate);
    }
}