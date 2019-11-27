<?php

namespace Paygreen\SDK;

use Exception;
use LogicException;
use Paygreen\SDK\Http\HttpApiRequest;
use Paygreen\SDK\Http\HttpClientCurl;
use Paygreen\SDK\Http\HttpClientStream;
use Paygreen\SDK\Http\IHttpClient;

class ApiClient
{
    /**
     * @var IHttpClient
     */
    protected $_httpClient;
    /**
     * @var ApiRequestFactory
     */
    protected $_requestFactory;

    public function __construct(IHttpClient $httpClient = null)
    {
        if (isset($httpClient)) {
            $this->_httpClient = $httpClient;
        } elseif (extension_loaded('curl')) {
            $this->_httpClient = new HttpClientCurl();
        } elseif (ini_get('allow_url_fopen')) {
            $this->_httpClient = new HttpClientStream();
        } else {
            throw new Exception("Invalid setup. Either add curl PHP extension or enable allow_url_fopen in php.ini");
        }
    }

    public function addConfiguration(ApiConfiguration $configuration): void
    {
        $this->_requestFactory = new ApiRequestFactory($configuration);
    }

    private function requestFactory(): ApiRequestFactory
    {
        if (!isset($this->_requestFactory)) {
            throw new LogicException("Missing API configuration");
        }
        return $this->_requestFactory;
    }

    /**
     * @param string $endpointKey
     * @param string $endpointValue
     * @param array|null $content
     * @return HttpApiRequest
     */
    protected function createRequest(string $endpointKey, string $endpointValue = '', array $content = null): HttpApiRequest
    {
        $req = $this->requestFactory()->create($endpointKey, $endpointValue, $content);

        return $req;
    }

    /**
     * Return method and url by function name
     *
     * @param $request
     * @return object page
     */
    protected function callAPI($request)
    {
        $page = $this->_httpClient->callRequest($request);

        if ($page === false) {
            return ((object)array('error' => 1));
        }

        return json_decode($page);
    }

    /**
     * @param string $endpointKey
     * @param string $endpointValue
     * @param array|null $content
     * @return object
     */
    protected function createRequestAndCallAPI(string $endpointKey, string $endpointValue = '', array $content = null)
    {
        $req = $this->createRequest($endpointKey, $endpointValue, $content);
        $res = $this->callAPI($req);

        return $res;
    }

    /**
     * @param string $endpointKey
     * @param string $endpointValue
     * @param array|null $content
     * @return object
     */
    private function getObjectOrError(string $endpointKey, string $endpointValue = '', array $content = null)
    {
        $object = $this->createRequestAndCallAPI($endpointKey, $endpointValue, $content);

        return isset($object->error)
            ? $object->error
            : $object;
    }


    /**
     * To check if private Key and Unique Id are valid
     * @return string json answer of false if activate != {0,1}
     */
    public function checkConfiguration()
    {
        $valid = $this->createRequestAndCallAPI(ApiEndpoint::CHECK_ID);

        if ($valid != false) {
            if (isset($valid->error)) {
                return $valid;
            }
            if ($valid->success == 0) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Get Status of the shop
     * @return string json
     */
    public function getStatusShop()
    {
        return $this->createRequestAndCallAPI(ApiEndpoint::GET_OBJECT, 'shop');
    }

    /**
     * To validate the shop
     *
     * @param int $activate 1 or 0 to active the account
     * @return string json answer of false if activate != {0,1}
     */
    public function validateShop($activate)
    {
        if ($activate != 1 && $activate != 0) {
            return false;
        }
        $content = array('activate' => $activate);

        return $this->createRequestAndCallAPI(ApiEndpoint::SHOP_VALIDATE, '', $content);
    }


    public function getPayinDetails($pid)
    {
        return $this->createRequestAndCallAPI(ApiEndpoint::PAYIN_DETAILS, $pid);
    }

    public function createCarbonPayin($data)
    {
        return $this->createRequestAndCallAPI(ApiEndpoint::PAYIN_CARBON, '', $data['content']);
    }

    public function createCashPayin($data)
    {
        return $this->createRequestAndCallAPI(ApiEndpoint::PAYIN_CASH, '', $data['content']);
    }

    public function createXTimePayin($data)
    {
        return $this->createRequestAndCallAPI(ApiEndpoint::PAYIN_XTIME, '', $data['content']);
    }

    public function createSubscriptionPayin($data)
    {
        return $this->createRequestAndCallAPI(ApiEndpoint::PAYIN_SUBSCRIPTION, '', $data['content']);
    }

    public function createTokenizePayin($data)
    {
        return $this->createRequestAndCallAPI(ApiEndpoint::PAYIN_TOKEN, '', $data['content']);
    }

    public function confirmPayin($pid)
    {
        return $this->createRequestAndCallAPI(ApiEndpoint::PAYIN_CONFIRM, $pid);
    }

    /**
     * Refund an order
     *
     * @param int $pid paygreen id of transaction
     * @param float $amount amount of refund
     * @return string json answer
     */
    public function refundPayin($pid, $amount = null)
    {
        if (empty($pid)) {
            return false;
        }

        $req = $this->createRequest(ApiEndpoint::PAYIN_REFUND, $pid);
        if ($amount != null) {
            $req->addContent(array('amount' => $amount * 100));
        }

        return $this->callAPI($req);
    }

    /**
     * Get rounding information for $paymentToken
     * @param $data
     * @return object
     */
    public function getRoundingInfo($data)
    {
        return $this->getObjectOrError(ApiEndpoint::SOLIDARITY_GET, $data['paymentToken']);
    }

    /**
     * @param $data
     * @return object
     */
    public function validateRounding($data)
    {
        return $this->getObjectOrError(ApiEndpoint::SOLIDARITY_VALIDATE, $data['paymentToken']);
    }

    /**
     * @param $data
     * @return object
     */
    public function refundRounding($data)
    {
        $content = array('paymentToken' => $data['paymentToken']);

        return $this->getObjectOrError(ApiEndpoint::SOLIDARITY_REFUND, $data['paymentToken'], $content);
    }


    /**
    * Get shop information
    * @return array|string
    */
    public function getAccountData()
    {
        $account = $this->createRequestAndCallAPI(ApiEndpoint::GET_OBJECT, 'account');
        if ($account == false || isset($account->error)) {
            return $account;
        }

        $bank  = $this->createRequestAndCallAPI(ApiEndpoint::GET_OBJECT, 'bank');
        if ($bank == false || isset($bank->error)) {
            return $bank;
        }

        $shop = $this->createRequestAndCallAPI(ApiEndpoint::GET_OBJECT, 'shop');
        if ($shop == false || isset($shop->error)) {
            return $shop;
        }

        return $this->buildAccountData($account, $bank, $shop);
    }

    private function buildAccountData($account, $bank, $shop)
    {
        $accountData = array();
        $accountData['siret'] = $account->data->siret;

        foreach ($bank->data as $rib) {
            if ($rib->isDefault == "1") {
                $accountData['IBAN']  = $rib->iban;
            }
        }

        $accountData['url'] = $shop->data->url;
        $accountData['modules'] = $shop->data->modules;
        $accountData['solidarityType'] = $shop->data->extra->solidarityType;
        if (isset($shop->data->businessIdentifier)) {
            $accountData['siret'] = $shop->data->businessIdentifier;
        }
        $accountData['valide'] = true;

        if (empty($accountData['url']) && empty($accountData['siret']) && empty($accountData['IBAN'])) {
            $accountData['valide'] = false;
        }

        return $accountData;
    }

    /**
     * Authentication to server paygreen
     *
     * @param string $email email of account paygreen
     * @param string $name name of shop
     * @param null $ipAddress
     * @return string json data
     */
    public function getOAuthServerAccess($email, $name, $ipAddress = null)
    {
        if (!isset($ipAddress)) {
            $ipAddress = $_SERVER['ADDR'];
        }
        $content = array(
            "ipAddress" => $ipAddress,
            "email" => $email,
            "name" => $name
        );

        $req = $this->requestFactory()->create(ApiEndpoint::OAUTH_ACCESS);
        $req->addContent($content);

        return $this->callAPI($req);
    }

    /**
     * 3
     * return url of Authorization
     * @return string url of Authorization
     */
    public function getOAuthAuthorizeEndpoint()
    {
        return ApiEndpoint::getEndpointFormat(ApiEndpoint::OAUTH_AUTHORIZE);
    }

    /**
     * 4
     * return url of auth token
     * @return string url of Authentication
     */
    public function getOAuthTokenEndpoint()
    {
        return ApiEndpoint::getEndpointFormat(ApiEndpoint::OAUTH_TOKEN);
    }
}
