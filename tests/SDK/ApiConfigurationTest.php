<?php

namespace Paygreen\Tests\SDK;

use Paygreen\SDK\ApiConfiguration;
use ArgumentCountError;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ApiConfigurationTest extends TestCase
{
    public function testMissingArgumentsShouldThrow()
    {
        $this->expectException(ArgumentCountError::class);

        $config = new ApiConfiguration();
    }

    public function testEmptyPrivateKeyShouldThrow()
    {
        $this->expectException(InvalidArgumentException::class);

        $config = new ApiConfiguration('uniqueIdentifier', '');
    }

    public function testNoApiHostShouldSucceed()
    {
        $config = new ApiConfiguration('uniqueIdentifier', 'privateKey');

        $this->assertSame('uniqueIdentifier', $config->getUniqueIdentifier());
        $this->assertSame('privateKey', $config->getPrivateKey());
    }

    public function testAllArgumentsShouldSucceed()
    {
        $config = new ApiConfiguration('uniqueIdentifier', 'privateKey', 'https://preprod.paygreen.fr');

        $this->assertSame('uniqueIdentifier', $config->getUniqueIdentifier());
        $this->assertSame('privateKey', $config->getPrivateKey());
        $this->assertSame('https://preprod.paygreen.fr/api', $config->getApiServerUrl());
    }
}
