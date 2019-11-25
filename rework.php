<?php
$loader = require_once __DIR__ . '/vendor/autoload.php';
$loader->add('Paygreen\\', __DIR__ . './src/');

use Paygreen\SDK;

$client = SDK\ApiClient::getInstance(
    'f3d64445bb5229c50c1b8c95760686ae',
    '095d-4211-96bd-987cb9f4a695',
    'https://preprod.paygreen.fr');

var_dump($client->getStatusShop());
