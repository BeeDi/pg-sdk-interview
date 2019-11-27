<?php

namespace Paygreen\SDK;

use InvalidArgumentException;
use Paygreen\SDK\Http\HttpVerb;

class ApiEndpoint
{
    const CHECK_ID = 'are-valid-ids';
    const OAUTH_AUTHORIZE = 'oauth';
    const OAUTH_TOKEN = 'oauth-token';
    const OAUTH_ACCESS = 'oauth-access';
    const GET_OBJECT = 'get-data';
    const SHOP_VALIDATE = 'validate-shop';
    const PAYIN_CARBON = 'payin-ccarbone';
    const PAYIN_CASH = 'create-cash';
    const PAYIN_SUBSCRIPTION = 'create-subscription';
    const PAYIN_TOKEN = 'create-tokenize' ;
    const PAYIN_XTIME = 'create-xtime' ;
    const PAYIN_DETAILS = 'payin-details';
    const PAYIN_CONFIRM = 'delivery' ;
    const PAYIN_REFUND = 'refund' ;
    const SOLIDARITY_GET = 'get-rounding' ;
    const SOLIDARITY_VALIDATE = 'validate-rounding' ;
    const SOLIDARITY_REFUND = 'refund-rounding' ;

    private static $methodDictionary = array(
        ApiEndpoint::CHECK_ID => array(HttpVerb::GET, ''),
        ApiEndpoint::OAUTH_AUTHORIZE => array(HttpVerb::GET, '/auth/authorize'),
        ApiEndpoint::OAUTH_TOKEN => array(HttpVerb::POST, '/auth/access_token'),
        ApiEndpoint::OAUTH_ACCESS => array(HttpVerb::POST, '/auth'),
        ApiEndpoint::GET_OBJECT => array(HttpVerb::GET, '/%s'),
        ApiEndpoint::SHOP_VALIDATE => array(HttpVerb::PATCH, '/shop'),
        ApiEndpoint::PAYIN_CARBON => array(HttpVerb::POST, '/payins/ccarbone'),
        ApiEndpoint::PAYIN_CASH => array(HttpVerb::POST, '/payins/transaction/cash'),
        ApiEndpoint::PAYIN_SUBSCRIPTION => array(HttpVerb::POST, '/payins/transaction/subscription'),
        ApiEndpoint::PAYIN_TOKEN => array(HttpVerb::POST, '/payins/transaction/tokenize'),
        ApiEndpoint::PAYIN_XTIME => array(HttpVerb::POST, '/payins/transaction/xTime'),
        ApiEndpoint::PAYIN_DETAILS => array(HttpVerb::GET, '/payins/transaction/%s'),
        ApiEndpoint::PAYIN_CONFIRM => array(HttpVerb::PUT, '/payins/transaction/%s'),
        ApiEndpoint::PAYIN_REFUND => array(HttpVerb::DELETE, '/payins/transaction/%s'),
        ApiEndpoint::SOLIDARITY_GET => array(HttpVerb::GET, '/solidarity/%s'),
        ApiEndpoint::SOLIDARITY_VALIDATE => array(HttpVerb::PATCH, '/solidarity/%s'),
        ApiEndpoint::SOLIDARITY_REFUND => array(HttpVerb::DELETE, '/solidarity/%s')
    );

    /**
     * @param string $endpointKey
     * @return HttpVerb
     */
    public static function getEndpointVerb(string $endpointKey): string
    {
        if (!isset(ApiEndpoint::$methodDictionary[$endpointKey])) {
            throw new InvalidArgumentException("Unknown HttpRequest key: " . $endpointKey);
        }

        return ApiEndpoint::$methodDictionary[$endpointKey][0];
    }

    /**
     * @param string $endpointKey
     * @return string
     */
    public static function getEndpointFormat(string $endpointKey): string
    {
        if (!isset(ApiEndpoint::$methodDictionary[$endpointKey])) {
            throw new InvalidArgumentException("Unknown HttpRequest key: " . $endpointKey);
        }

        return ApiEndpoint::$methodDictionary[$endpointKey][1];
    }
}
