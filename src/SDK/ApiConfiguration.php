<?php

namespace Paygreen\SDK;

use InvalidArgumentException;

class ApiConfiguration
{
    private $uniqueIdentifier = '';
    private $privateKey = '';
    private $apiServerUrl = 'https://paygreen.fr/api';

    public function __construct($uniqueIdentifier, $privateKey, $apiHost = '')
    {
        $this->setApiServerUrl($apiHost);
        $this->setUniqueIdentifier($uniqueIdentifier);
        $this->setPrivateKey($privateKey);
    }

    public function getUniqueIdentifier()
    {
        $stripedUniqueIdentifier = $this->uniqueIdentifier;
        if (substr($this->uniqueIdentifier, 0, 2) == 'PP') {
            $stripedUniqueIdentifier = substr($this->uniqueIdentifier, 2);
        }
        return $stripedUniqueIdentifier;
    }

    public function getApiServerUrl()
    {
        return $this->apiServerUrl;
    }

    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    private function setApiServerUrl($apiHost)
    {
        if (!empty($apiHost)) {
            $this->apiServerUrl = $apiHost . '/api';
        }
    }

    private function setUniqueIdentifier($uniqueIdentifier)
    {
        if (empty($uniqueIdentifier)) {
            throw new InvalidArgumentException("Missing UniqueIdentifier");
        }

        $this->uniqueIdentifier = $uniqueIdentifier;
    }

    private function setPrivateKey($privateKey)
    {
        if (empty($privateKey)) {
            throw new InvalidArgumentException("Missing PrivateKey");
        }

        $this->privateKey = $privateKey;
    }
}
