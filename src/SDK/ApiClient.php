<?php

namespace Paygreen\SDK;

use Exception;
use InvalidArgumentException;
use LogicException;
use Paygreen\SDK\Http\HttpApiRequest;
use Paygreen\SDK\Http\HttpClientCurl;
use Paygreen\SDK\Http\HttpClientStream;
use Paygreen\SDK\Http\HttpVerb;
use Paygreen\SDK\ApiConfiguration;

class ApiClient
{
    private $configuration;
    private $httpClient;

    public function __construct($httpClient = null)
    {
        if (isset($httpClient)) {
            $this->httpClient = $httpClient;
        } elseif (extension_loaded('curl')) {
            $this->httpClient = new HttpClientCurl();
        } elseif (ini_get('allow_url_fopen')) {
            $this->httpClient = new HttpClientStream();
        } else {
            throw new Exception("Invalid configuration. Either add curl PHP extension or enable allow_url_fopen in php.ini");
        }
    }

    public function addConfiguration(ApiConfiguration $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function getConfiguration(): ApiConfiguration
    {
        if (!isset($this->configuration)) {
            throw new LogicException("Missing " .__CLASS__." configuration");
        }

        return $this->configuration;
    }

    private static $methodDictionary = array(
        'create-cash' => array(HttpVerb::POST, '/payins/transaction/cash'),
        'get-data' => array(HttpVerb::GET, '/%s')
    );

    /**
     * @param string $endpointKey
     * @return HttpVerb
     */
    private function getEndpointVerb(string $endpointKey): string
    {
        if (!isset(ApiClient::$methodDictionary[$endpointKey])) {
            throw new InvalidArgumentException("Unknown HttpRequest key: " . $endpointKey);
        }

        return ApiClient::$methodDictionary[$endpointKey][0];
    }

    /**
     * @param string $endpointKey
     * @return string
     */
    private function getEndpointFormat(string $endpointKey): string
    {
        if (!isset(ApiClient::$methodDictionary[$endpointKey])) {
            throw new InvalidArgumentException("Unknown HttpRequest key: " . $endpointKey);
        }

        return ApiClient::$methodDictionary[$endpointKey][1];
    }

    /**
     * @param string $endpointKey
     * @param string $endpointValue
     * @param array|null $content
     * @return HttpApiRequest
     */
    private function buildApiRequest(string $endpointKey, string $endpointValue = '', array $content = null): HttpApiRequest
    {
        if (!isset(ApiClient::$methodDictionary[$endpointKey])) {
            throw new InvalidArgumentException("Unknown HttpRequest key");
        }

        $req = new HttpApiRequest(
            $this->getConfiguration()->getPrivateKey(),
            $this->getEndpointVerb($endpointKey),
            $this->buildUrl($this->getConfiguration(), $this->getEndpointFormat($endpointKey), $endpointValue)
        );

        if (isset($content)) {
            $req->addContent($content);
        }

        return $req;
    }

    /**
     * @param \Paygreen\SDK\ApiConfiguration $configuration
     * @param string $endpoint
     * @param string $value (optional)
     * @return string
     */
    private function buildUrl(ApiConfiguration $configuration, string $endpoint, string $value = ''): string
    {
        $baseUrl = $configuration->getApiServerUrl() . '/' . $configuration->getUniqueIdentifier();
        if (isset($value)) {
            return $baseUrl . sprintf($endpoint, $value);
        } else {
            return $baseUrl . $endpoint;
        }
    }


    /**
     * Return method and url by function name
     *
     * @param $request
     * @return object page
     */
    private function requestApi($request)
    {
        $page = $this->httpClient->callRequest($request);

        if ($page === false) {
            return ((object)array('error' => 1));
        }

        return json_decode($page);
    }


    /**
     * Get Status of the shop
     * @return string json datas
     */
    public function getStatusShop()
    {
        $request = $this->buildApiRequest('get-data', 'shop');
        return $this->requestApi($request);
    }










    /**
     * Authentication to server paygreen
     *
     * @param string $email email of account paygreen
     * @param string $name name of shop
     * @param string $phone phone number, can be null
     * @param null $ipAddress
     * @return string json datas
     */
    public function getOAuthServerAccess($email, $name, $phone = null, $ipAddress = null)
    {
        if (!isset($ipAddress)) {
            $ipAddress = $_SERVER['ADDR'];
        }
        $subParam = array(
            "ipAddress" => $ipAddress,
            "email" => $email,
            "name" => $name
        );
        $datas['content'] = $subParam ;

        return $this->requestApi('oAuth-access', $datas);
    }

    /**
    * 3
    * return url of Authorization
    * @return string url of Authorization
    */
    public function getOAuthAuthorizeEndpoint()
    {
        return $this->getConfiguration()->getApiServerUrl().'/auth/authorize';
    }

    /**
    * 4
    * return url of auth token
    * @return string url of Authentication
    */
    public function getOAuthTokenEndpoint()
    {
        return $this->getConfiguration()->getApiServerUrl().'/auth/access_token';
    }

    /**
    * return url of Authentication
    * 2
    * @return string url of Authentication
    */
    private function getOAuthDeclareEndpoint()
    {
        return $this->getConfiguration()->getApiServerUrl().'/auth';
    }

    public function getTransactionInfo($pid)
    {
        return $this->requestApi('get-datas', array('pid' => $pid));
    }



    /**
    * Refund an order
    *
    * @param int $pid paygreen id of transaction
    * @param float $amount amount of refund
    * @return string json answer
    */
    public function refundOrder($pid, $amount)
    {
        if (empty($pid)) {
            return false;
        }

        $datas = array('pid' => $pid);
        if ($amount != null) {
            $datas['content'] = array('amount' => $amount * 100);
        }

        return $this->requestApi('refund', $datas);
    }

    public function sendFingerprintDatas($data)
    {
        $datas['content'] = $data;
        return $this->requestApi('send-ccarbone', $datas);
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
        $datas['content'] = array('activate' => $activate);
        return $this->requestApi('validate-shop', $datas);
    }

    /**
    * To check if private Key and Unique Id are valids
    *
    * @return string json answer of false if activate != {0,1}
    */
    public function validIdShop()
    {
        $valid = $this->requestApi('are-valid-ids', null);

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
    * Get shop informations
    * @return string json datas
    */
    public function getAccountInfos()
    {
        $infosAccount = array();

        $account = $this->requestApi('get-data', array('type'=>'account'));
        if ($this->isContainsError($account)) {
            return $account->error;
        }
        if ($account == false) {
            return false;
        }
        $infosAccount['siret'] = $account->data->siret;

        $bank  = $this->requestApi('get-data', array('type' => 'bank'));
        if ($this->isContainsError($bank)) {
            return $bank->error;
        }
        if ($bank == false) {
            return false;
        }

        foreach ($bank->data as $rib) {
            if ($rib->isDefault == "1") {
                $infosAccount['IBAN']  = $rib->iban;
            }
        }

        $shop = $this->requestApi('get-data', array('type'=> 'shop'));
        if ($this->isContainsError($bank)) {
            return $shop->error;
        }
        if ($shop == false) {
            return false;
        }
        $infosAccount['url'] = $shop->data->url;
        $infosAccount['modules'] = $shop->data->modules;
        $infosAccount['solidarityType'] = $shop->data->extra->solidarityType;

        if (isset($shop->data->businessIdentifier)) {
            $infosAccount['siret'] = $shop->data->businessIdentifier;
        }

        $infosAccount['valide'] = true;

        if (empty($infosAccount['url']) && empty($infosAccount['siret']) && empty($infosAccount['IBAN'])) {
            $infosAccount['valide'] = false;
        }
        return $infosAccount;
    }

    /**
     * Get rounding informations for $paiementToken
     * @param $datas
     * @return string json datas
     */
    public function getRoundingInfo($datas)
    {
        $transaction = $this->requestApi('get-rounding', $datas);
        if ($this->isContainsError($transaction)) {
            return $transaction->error;
        }
        return $transaction;
    }

    public function validateRounding($datas)
    {
        $validate = $this->requestApi('validate-rounding', $datas);
        if ($this->isContainsError($validate)) {
            return $validate->error;
        }
        return $validate;
    }

    public function refundRounding($datas)
    {
        $datas['content'] = array('paymentToken' => $datas['paymentToken']);
        $refund = $this->requestApi('refund-rounding', $datas);
        if ($this->isContainsError($refund)) {
            return $refund->error;
        }
        return $refund;
    }

    public function validDeliveryPayment($pid)
    {
        return $this->requestApi('delivery', array('pid' => $pid));
    }

    public function createCash($data)
    {
        return $this->requestApi('create-cash', $data);
    }

    public function createXTime($data)
    {
        return $this->requestApi('create-xtime', $data);
    }

    public function createSubscription($data)
    {
        return $this->requestApi('create-subscription', $data);
    }

    public function createTokenize($data)
    {
        return $this->requestApi('create-tokenize', $data);
    }



    /**
    * Check if error is defined in object
    * @param object $var
    * @return boolean
    */
    private function isContainsError($var)
    {
        if (isset($var->error)) {
            return true;
        }
        return false;
    }



    /************************************************************
     * Private functions called by requestApi
     ***********************************************************
     * @param $datas
     * @param $http
     * @return array
     */
    private function oauth_access($datas, $http)
    {
        return ($data = array(
            'method'    =>  'POST',
            'url'       =>  self::getOAuthDeclareEndpoint(),
            'http'      =>  ''
        ));
    }

    private function validate_shop($datas, $http)
    {
        return ($data = array(
            'method'    =>  'PATCH',
            'url'       =>  $this->configuration->getApiServerUrl().'/'.$this->configuration->getUniqueIdentifier().'/shop',
            'http'      =>  $http
        ));
    }

    private function refund($datas, $http)
    {
        if (empty($datas['pid'])) {
            return (false);
        }
        return ($data = array(
            'method'    =>  'DELETE',
            'url'       =>  $this->configuration->getApiServerUrl().'/'.$this->configuration->getUniqueIdentifier().'/payins/transaction/'.$datas['pid'],
            'http'      =>  $http
        ));
    }

    private function are_valid_ids($datas, $http)
    {
        return ($data = array(
            'method'    =>  'GET',
            'url'       =>  $this->configuration->getApiServerUrl().'/'.$this->configuration->getUniqueIdentifier(),
            'http'      =>  $http
        ));
    }

    private function get_data($datas, $http)
    {
        return ($data = array(
            'method'    =>  'GET',
            'url'       =>  $this->configuration->getApiServerUrl().'/'.$this->configuration->getUniqueIdentifier().'/'.$datas['type'],
            'http'      =>  $http
        ));
    }

    private function delivery($datas, $http)
    {
        return ($data = array(
            'method'    =>  'PUT',
            'url'       =>  $this->configuration->getApiServerUrl().'/'.$this->configuration->getUniqueIdentifier().'/payins/transaction/'.$datas['pid'],
            'http'      =>  $http
        ));
    }

    private function create_cash($datas, $http)
    {
        return ($data = array(
            'method'    =>  'POST',
            'url'       =>  $this->configuration->getApiServerUrl().'/'.$this->configuration->getUniqueIdentifier().'/payins/transaction/cash',
            'http'      =>  $http
        ));
    }

    private function create_subscription($datas, $http)
    {
        return ($data = array(
            'method'    =>  'POST',
            'url'       =>  $this->configuration->getApiServerUrl().'/'.$this->configuration->getUniqueIdentifier().'/payins/transaction/subscription',
            'http'      =>  $http
        ));
    }

    private function create_tokenize($datas, $http)
    {
        return ($data = array(
            'method'    =>  'POST',
            'url'       =>  $this->configuration->getApiServerUrl().'/'.$this->configuration->getUniqueIdentifier().'/payins/transaction/tokenize',
            'http'      =>  $http
        ));
    }

    private function create_xtime($datas, $http)
    {
        return ($data = array(
            'method'    =>  'POST',
            'url'       =>  $this->configuration->getApiServerUrl().'/'.$this->configuration->getUniqueIdentifier().'/payins/transaction/xTime',
            'http'      =>  $http
        ));
    }

    private function get_datas($datas, $http)
    {
        if (empty($datas['pid'])) {
            return false;
        }
        return ($data = array(
            'method'    =>  'GET',
            'url'       =>  $this->configuration->getApiServerUrl().'/'.$this->configuration->getUniqueIdentifier().'/payins/transaction/'.$datas['pid'],
            'http'      =>  $http
        ));
    }

    private function get_rounding($datas, $http)
    {
        return ($data = array(
            'method'    =>  'GET',
            'url'       =>  $this->configuration->getApiServerUrl().'/'.$this->configuration->getUniqueIdentifier().'/solidarity/'.$datas['paymentToken'],
            'http'      =>  $http
        ));
    }

    private function validate_rounding($datas, $http)
    {
        return ($data = array(
            'method'    =>  'PATCH',
            'url'       =>  $this->configuration->getApiServerUrl().'/'.$this->configuration->getUniqueIdentifier().'/solidarity/'.$datas['paymentToken'],
            'http'      =>  $http
        ));
    }

    private function refund_rounding($datas, $http)
    {
        return ($data = array(
            'method'    =>  'DELETE',
            'url'       =>  $this->configuration->getApiServerUrl().'/'.$this->configuration->getUniqueIdentifier().'/solidarity/'.$datas['paymentToken'],
            'http'      =>  $http
        ));
    }

    private function send_ccarbone($datas, $http)
    {
        return ($data = array(
            'method' => 'POST',
            'url' => $this->configuration->getApiServerUrl().'/'.$this->configuration->getUniqueIdentifier().'/payins/ccarbone',
            'http' => $http
        ));
    }
}
