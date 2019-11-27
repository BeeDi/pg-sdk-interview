<?php
$loader = require_once __DIR__ . '/vendor/autoload.php';
$loader->add('Paygreen\\', __DIR__ . './src/');

use Paygreen\SDK\ApiConfiguration;
use Paygreen\SDK\ApiClient;

$api = new ApiClient();
$api->addConfiguration(new ApiConfiguration(
    'f3d64445bb5229c50c1b8c95760686ae',
    '095d-4211-96bd-987cb9f4a695',
    'https://preprod.paygreen.fr'
));
//$api->checkConfiguration();
//$api->getStatusShop();
//$api->getAccountData();
$cashCreation = $api->createCashPayin(array(
    'content' =>
        array(
            "orderId" => "123456",
            "amount" => 9000,
            "currency" => "EUR",
            "paymentType" => "CB",
            "returned_url" => "http://example.com/retour-client",
            "notified_url" => "http://example.com/retour-server",
            //"idFingerprint" => 0,
            "buyer" => array(
                "id" => "123654789",
                "lastName" => "Pay",
                "firstName" => "Green",
                "email" => "contact@paygreen.fr",
                "country" => "FR"
            ),
            "metadata" => array(
                "orderId" => "test-123",
                "display" => "0"
            ),
            "eligibleAmount" => array(
                "ANCV" => "1000",
                "RESTOFLASH" => "0"
            ),
            "card" => array(
                "token" => "abcdef1234567890"
            )
        )
));
$details = $api->getPayinDetails($cashCreation->data->id);
$refund = $api->refundPayin($cashCreation->data->id);
var_dump($refund);

