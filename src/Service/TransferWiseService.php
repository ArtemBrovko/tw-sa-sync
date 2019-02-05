<?php
/**
 * @author Artem Brovko <artem.brovko@modera.net>
 * @copyright 2019 Modera Foundation
 */

namespace App\Service;


use Psr\Http\Message\ResponseInterface;

class TransferWiseService
{
    const PERSONAL_ACCOUNT_TYPE_NAME = 'personal';

    const TRANSFER_STATUS_INCOMING_PAYMENT_WAITING = 'incoming_payment_waiting';
    const TRANSFER_STATUS_PROCESSING = 'processing';
    const TRANSFER_STATUS_FUNDS_CONVERTED = 'funds_converted';
    const TRANSFER_STATUS_OUTGOING_PAYMENT_SENT = 'outgoing_payment_sent';
    const TRANSFER_STATUS_BOUNCED_BACK = 'bounced_back';
    const TRANSFER_STATUS_FUNDS_REFUNDED = 'funds_refunded';

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
                if ($profile->type === self::PERSONAL_ACCOUNT_TYPE_NAME) {
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
        return $this->makeRequest('profiles');
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
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTransfers()
    {
        return json_decode($this->getRequestBody($this->makeRequest('transfers')));
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
     * @return string
     * @throws \Exception
     */
    private function getEndpointUrl()
    {
        $testMode = true;

        if ($testMode) {
            return 'https://api.sandbox.transferwise.tech/v1/';
        } else {
            throw new \Exception('Production mode is not implemented yet');
        }
    }

    private function getAPIToken()
    {
        return '5a7904d4-c14d-4095-b1e4-12000e20215b';
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

}

