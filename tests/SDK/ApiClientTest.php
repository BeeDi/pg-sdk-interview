<?php

namespace Paygreen\Tests\SDK;

use LogicException;
use Paygreen\SDK\ApiClient;
use Paygreen\SDK\ApiConfiguration;
use Paygreen\SDK\Http\HttpClientCurl;
use Paygreen\SDK\Http\IHttpClient;
use PHPUnit\Framework\TestCase;
use Paygreen\Tests\SDK;

class ApiClientTest extends TestCase
{
    public function testCallAPIWithoutConfigurationShouldThrow()
    {
        $this->expectException(LogicException::class);

        $stubHttpClient = $this->createStub(HttpClientCurl::class);
        $stubHttpClient->method('callRequest')
            ->willReturn('{"stub":"has been called"}');

        $api = new ApiClient($stubHttpClient);
        $api->getStatusShop();
    }

    public function testCallAPIWithMockClientShouldSucceed()
    {
        $stubHttpClient = $this->createStub(HttpClientCurl::class);
        $stubHttpClient->method('callRequest')
            ->willReturn('{"stub":"has been called"}');

        $api = new ApiClient($stubHttpClient);
        $api->addConfiguration(new ApiConfiguration('ui', 'pk'));
        $res = $api->getStatusShop();

        $this->assertIsObject($res);
        $this->assertObjectHasAttribute('stub', $res);
        $this->assertSame("has been called", $res->stub);
    }
}