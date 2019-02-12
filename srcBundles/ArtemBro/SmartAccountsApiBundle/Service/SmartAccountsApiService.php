<?php

namespace ArtemBro\SmartAccountsApiBundle\Service;

use ArtemBro\SmartAccountsApiBundle\Client\Client;

/**
 * @author Artem Brovko <artem.brovko@modera.net>
 * @copyright 2019 Modera Foundation
 */
class SmartAccountsApiService
{

    public function getClient($apiKeyPublic, $apiKeyPrivate)
    {
        return new Client($apiKeyPublic, $apiKeyPrivate);
    }

    private function getApiKeyPublic()
    {
        return '60aa478772a141aba0ae';
    }

    private function getAPIKeyPrivate()
    {
        return 'bb5ebed5ce0d45f6b6b12701932c5468';
    }
}