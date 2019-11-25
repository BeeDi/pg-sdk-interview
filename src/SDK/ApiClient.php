<?php

namespace Paygreen\SDK;

use Paygreen\SDK\ApiConfiguration;

class ApiClient
{
    public $configuration;

    public function __construct()
    {
    }

    /**
    * Authentication to server paygreen
    *
    * @param string $email email of account paygreen
    * @param string $name name of shop
    * @param string $phone phone number, can be null
    * @param string $ipAdress ip Adress current, if null autodetect
    * @return string json datas
    *
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
    public function getOAuthAutorizeEndpoint()
    {
        return $this->configuration->getApiServerUrl().'/auth/authorize';
    }

    /**
    * 4
    * return url of auth token
    * @return string url of Authentication
    */
    public function getOAuthTokenEndpoint()
    {
        return $this->configuration->getApiServerUrl().'/auth/access_token';
    }

    /**
    * return url of Authentication
    * 2
    * @return string url of Authentication
    */
    private function getOAuthDeclareEndpoint()
    {
        return $this->configuration->getApiServerUrl().'/auth';
    }

    public function getTransactionInfo($pid)
    {
        return $this->requestApi('get-datas', array('pid' => $pid));
    }

    /**
    * Get Status of the shop
    * @return string json datas
    */
    public function getStatusShop()
    {
        return $this->requestApi('get-data', array('type'=>'shop'));
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
    * @param string $UI unique id
    * @param string $CP private key
    * @param string $paiementToken paiementToken
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

    /**
    * Return method and url by function name
    *
    * @param string $function
    * @param array $datas
    * @return object page
    */
    private function requestApi($function, $datas = null)
    {
        $http           = "Authorization: Bearer ".$this->configuration->getPrivateKey();

        $lowerName      = strtolower($function);
        $function_name  = str_replace('-', '_', $lowerName);
        $datas_request  = $this->$function_name($datas, $http);
        $content        = '';
        if (isset($datas['content'])) {
            $content = json_encode($datas['content']);
        }
        if (extension_loaded('curl')) {
            $page = $this->request_api_curl($datas_request, $content);
        } elseif (ini_get('allow_url_fopen')) {
            $page = $this->request_api_fopen($datas_request, $content);
        } else {
            return ((object)array('error' => 0));
        }
        if ($page === false) {
            return ((object)array('error' => 1));
        }
        return json_decode($page);
    }

    private function request_api_curl($datas_request, $content)
    {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            // CURLOPT_SSL_VERIFYPEER => false,
            // CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_URL => $datas_request['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $datas_request['method'],
            CURLOPT_POSTFIELDS => $content,
            CURLOPT_HTTPHEADER => array(
                "accept: application/json",
                $datas_request['http'],
                "cache-control: no-cache",
                "content-type: application/json",
                ),
        ));
        $page = curl_exec($ch);
        curl_close($ch);
        return ($page);
    }

    private function request_api_fopen($datas_request, $content)
    {
        $opts = array(
            'http' => array(
                'method'    =>  $datas_request['method'],
                'header'    =>  "Accept: application/json\r\n" .
                "Content-Type: application/json\r\n".
                $datas_request['http'],
                'content'   =>  $content
            )
        );
        $context = stream_context_create($opts);
        $page = @file_get_contents($datas_request['url'], false, $context);
        return ($page);
    }

    /************************************************************
                Private functions called by requestApi
    ************************************************************/
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
