<?php
/**
 * @author Artem Brovko <artem.brovko@modera.net>
 * @copyright 2019 Modera Foundation
 */

namespace ArtemBro\SmartAccountsApiBundle\Client;

use ArtemBro\SmartAccountsApiBundle\Model\Client as ClientModel;
use ArtemBro\SmartAccountsApiBundle\Model\Payment;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Client
{
    /**
     * @var string
     */
    private $apiKeyPublic;

    /**
     * @var string
     */
    private $apiKeyPrivate;

    /**
     * Client constructor.
     *
     * @param string $apiKeyPublic
     * @param string $apiKeyPrivate
     */
    public function __construct(string $apiKeyPublic, string $apiKeyPrivate)
    {
        $this->apiKeyPublic = $apiKeyPublic;
        $this->apiKeyPrivate = $apiKeyPrivate;
    }

    /**
     * @return mixed|ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPurchases($dateFrom)
    {
        return $this->makeRequest('purchasesales/payments:get', [
            'query' => [
                'dateFrom' => $dateFrom
            ]
        ]);
    }

    /**
     * @param string $endpoint
     * @param array $params
     *
     * @return mixed|ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function makeRequest(string $endpoint, $params = array())
    {
        $client = $this->getGuzzleClient();

        return $client->request('GET', $endpoint, $params);
    }

    /**
     * @return \GuzzleHttp\Client
     * @throws \Exception
     */
    private function getGuzzleClient()
    {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());

        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            $query = $uri->getQuery();

            if (!empty($query)) {
                $query .= '&';
            }

            $queryWithTimestampAndApiKey = $query
                . 'timestamp=' . $this->getCurrentTimestamp()
                . '&apikey=' . $this->getApiKeyPublic();

            $apiSignature = $this->getAPISignature($queryWithTimestampAndApiKey, $request->getBody()->getContents());

            return $request
                ->withUri(
                    $request->getUri()->withQuery($queryWithTimestampAndApiKey . '&signature=' . $apiSignature));
        }));

        $client = new \GuzzleHttp\Client(array(
            /*            'defaults' => array(
                            'verify' => false
                        ),*/
            'base_uri' => $this->getApiBaseURL(),
            'headers'  => array(
                'Content-Type' => 'application/json'
            ),
            'handler'  => $stack
        ));

        return $client;
    }

    public function getCurrentTimestamp()
    {
        $date = new \DateTime('now', new \DateTimeZone('Europe/Helsinki'));

        return $date->format('dmYHis');
    }

    /**
     * @return string
     */
    public function getApiKeyPublic(): string
    {
        return $this->apiKeyPublic;
    }

    /**
     * @param string $apiKeyPublic
     */
    public function setApiKeyPublic(string $apiKeyPublic): void
    {
        $this->apiKeyPublic = $apiKeyPublic;
    }

    public function getAPISignature($url, $body)
    {
        return hash_hmac('sha256', $url . $body, $this->getAPIKeyPrivate());
    }

    /**
     * @return string
     */
    public function getApiKeyPrivate(): string
    {
        return $this->apiKeyPrivate;
    }

    /**
     * @param string $apiKeyPrivate
     */
    public function setApiKeyPrivate(string $apiKeyPrivate): void
    {
        $this->apiKeyPrivate = $apiKeyPrivate;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getApiBaseURL()
    {
        $testMode = true;

        if ($testMode) {
            return 'https://sa.SmartAccountsApi.eu/api/';
        } else {
            throw new \Exception('Production mode is not implemented yet');
        }
    }

    /**
     * @return mixed|ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPurchase($id)
    {
        return $this->makeRequest('purchasesales/payments:get', [
            'query' => [
                'id' => $id
            ]
        ]);
    }

    /**
     * @param int $page
     *
     * @return false|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getClients($page = 1)
    {
        return $this->getRequestBody($this->makeRequest('/api/purchasesales/clients:get', array(
            'query' => array(
                'pageNumber' => $page
            )
        )));
    }

    /**
     * @param ResponseInterface $request
     *
     * @return false|string
     */
    private function getRequestBody(ResponseInterface $request)
    {
        if ($request->getStatusCode() === 200) {
            return $request->getBody()->getContents();
        } else {
            return false;
        }
    }

    /**
     * @param ClientModel $client
     *
     * @return false|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendClient(ClientModel $client)
    {
        return $this->getRequestBody($this->makePostRequest('/api/purchasesales/clients:add', array(
            'body' => json_encode($client->getWriteObject())
        )));
    }

    /**
     * @param string $endpoint
     * @param array $params
     *
     * @return mixed|ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function makePostRequest(string $endpoint, $params = array())
    {
        $client = $this->getGuzzleClient();

        return $client->request('POST', $endpoint, $params);
    }

    /**
     * @param $nameOrRegCode
     *
     * @return false|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getClientByNameOrRegCode($nameOrRegCode)
    {
        return $this->getRequestBody($this->makeRequest('/api/purchasesales/clients:get', array(
            'query' => array(
                'nameOrRegCode' => $nameOrRegCode
            )
        )));

    }

    /**
     * @return false|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAccounts()
    {
        return $this->getRequestBody($this->makeRequest('/api/settings/accounts:get'));
    }

    /**
     * @return false|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getBankAccounts()
    {
        return $this->getRequestBody($this->makeRequest('/api/settings/bankaccounts:get'));
    }

    /**
     * @param Payment $payment
     *
     * @return mixed|ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendPurchase(Payment $payment)
    {
        return $this->makePostRequest('purchasesales/payments:add', [
            'body' => json_encode($payment->getWriteObject())
        ]);
    }
}