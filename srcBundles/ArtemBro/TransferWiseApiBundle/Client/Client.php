<?php
/**
 * @author Artem Brovko <brovko.artem@gmail.com>
 * @copyright 2019 Artem Brovko
 */


namespace ArtemBro\TransferWiseApiBundle\Client;

use ArtemBro\TransferWiseApiBundle\Service\TransferWiseApiService;
use Psr\Http\Message\ResponseInterface;

class Client
{
    /**
     * @var string
     */
    private $apiToken;

    /**
     * @var string
     */
    private $env;

    /**
     * Client constructor.
     *
     * @param string $apiToken
     */
    public function __construct(string $apiToken, string $env = TransferWiseApiService::API_ENVIRONMENT_DEV)
    {
        $this->apiToken = $apiToken;
        $this->env = $env;
    }

    /**
     * @return mixed
     */
    public function getPersonalProfile()
    {
//        $profilesRequest = $this->getProfiles();
//        $response = $this->getRequestBody($profilesRequest);

        //Stub
        $response = '[{"id":2980,"type":"personal","details":{"firstName":"Artem","lastName":"Brovko","dateOfBirth":"1980-09-19","phoneNumber":"+442038087139","avatar":null,"occupation":null,"primaryAddress":7105100}},{"id":2981,"type":"business","details":{"name":"Artem Brovko Business","registrationNumber":"07209813","acn":null,"abn":null,"arbn":null,"companyType":"LIMITED","companyRole":"OWNER","descriptionOfBusiness":"IT_SERVICES","primaryAddress":7105101,"webpage":null}}]';

        if ($response) {
            $profiles = json_decode($response);

            foreach ($profiles as $profile) {
                if ($profile->type === TransferWiseApiService::PERSONAL_ACCOUNT_TYPE_NAME) {
                    return $profile;
                }
            }
        }

        return null;
    }

    /**
     * @return mixed|ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getProfiles()
    {
        return json_decode($this->getRequestBody($this->makeRequest('profiles')));
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
        $client = new \GuzzleHttp\Client(array(
            /*            'defaults' => array(
                            'verify' => false
                        ),*/
            'base_uri' => $this->getEndpointUrl(),
            'headers'  => array(
                'Authorization' => 'Bearer ' . $this->getAPIToken(),
                'Content-Type'  => 'application/json'
            )
        ));

        return $client;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getEndpointUrl()
    {
        if ($this->env === TransferWiseApiService::API_ENVIRONMENT_PROD) {
            return 'https://api.transferwise.com/v1/';
        } else {
            return 'https://api.sandbox.transferwise.tech/v1/';
        }
    }

    /**
     * @return string
     */
    public function getApiToken(): string
    {
        return $this->apiToken;
    }

    /**
     * @param string $apiToken
     */
    public function setApiToken(string $apiToken): void
    {
        $this->apiToken = $apiToken;
    }

    /**
     * @param $id
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getProfile($id)
    {
        return json_decode($this->getRequestBody($this->makeRequest('profiles/' . $id)));
    }

    /**
     * @param $id
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAccount($id)
    {
        return json_decode($this->getRequestBody($this->makeRequest('accounts/' . $id)));
    }

    /**
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAccounts()
    {
        return json_decode($this->getRequestBody($this->makeRequest('accounts')));
    }

    /**
     * @param null $profile
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTransfers($profile = null)
    {
        return json_decode($this->getRequestBody($this->makeRequest('transfers', [
            'query' => [
                'profile' => $profile,
            ]
        ])));
    }

    /**
     * @param $id
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTransfer($id)
    {
        return json_decode($this->getRequestBody($this->makeRequest('transfers/' . $id)));
    }

    /**
     * Changes transfer status from incoming_payment_waiting to processing.
     *
     * @param $transferId
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function transferProcess($transferId)
    {
        return json_decode($this->getRequestBody($this->makeRequest('simulation/transfers/' . $transferId . '/processing')));
    }

    /**
     * Changes transfer status from processing to funds_converted.
     *
     * @param $transferId
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function transferConvertFunds($transferId)
    {
        return json_decode($this->getRequestBody($this->makeRequest('simulation/transfers/' . $transferId . '/funds_converted')));
    }

    /**
     * Changes transfer status from processing to funds_converted.
     *
     * @param $transferId
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function transferSendOutgoingPayment($transferId)
    {
        return json_decode($this->getRequestBody($this->makeRequest('simulation/transfers/' . $transferId . '/outgoing_payment_sent')));
    }

    /**
     * @param $transferId
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fund($transferId)
    {
        return json_decode($this->getRequestBody($this->makePostRequest('transfers/' . $transferId . '/payments', array(
            'body' => json_encode(array(
                'type' => "BALANCE"
            ))
        ))));
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
     * @param $startDate
     * @param $endDate
     * @param $status
     *
     * @return \Generator|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTransfersList($startDate, $endDate, $status = null)
    {
        $offset = 0;
        $limit = 100;

        do {
            $rows = json_decode($this->getRequestBody($this->makeRequest("transfers", [
                'query' => [
                    'offset'           => $offset,
                    'limit'            => $limit,
                    'createdDateStart' => $startDate,
                    'createdDateEnd'   => $endDate,
                    'status'           => $status
                ]
            ])));

            if ($rows) {
                foreach ($rows as $row) {
                    yield $row;
                }
            }

            $offset += $limit;
        } while (count($rows) === $limit);

        return;
    }


}