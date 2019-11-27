<?php

namespace Paygreen\SDK;

use Exception;
use Paygreen\SDK\Http\ApiRequestFactory;
use Paygreen\SDK\Http\HttpApiRequest;
use Paygreen\SDK\Http\HttpClientCurl;
use Paygreen\SDK\Http\HttpClientStream;

class ApiClient
{
    private $_httpClient;
    private $_requestFactory;

    public function __construct($httpClient = null)
    {
        if (isset($httpClient)) {
            $this->_httpClient = $httpClient;
        } elseif (extension_loaded('curl')) {
            $this->_httpClient = new HttpClientCurl();
        } elseif (ini_get('allow_url_fopen')) {
            $this->_httpClient = new HttpClientStream();
        } else {
            throw new Exception("Invalid configuration. Either add curl PHP extension or enable allow_url_fopen in php.ini");
        }
    }

    public function addConfiguration(ApiConfiguration $configuration): void
    {
        $this->_requestFactory = new ApiRequestFactory($configuration);
    }

    private function requestFactory(): ApiRequestFactory
    {
        return $this->_requestFactory;
    }

    /**
     * Return method and url by function name
     *
     * @param $request
     * @return object page
     */
    private function requestApi($request)
    {
        $page = $this->_httpClient->callRequest($request);

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
        $request = $this->requestFactory()->create(ApiEndpoint::GET_OBJECT, 'shop');
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
        $content = array(
            "ipAddress" => $ipAddress,
            "email" => $email,
            "name" => $name
        );

        $req = $this->requestFactory()->create(ApiEndpoint::OAUTH_ACCESS);
        $req->addContent($content);

        return $this->requestApi($req);
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

}
