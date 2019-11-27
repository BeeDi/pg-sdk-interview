<?php

namespace Paygreen\SDK;

use InvalidArgumentException;

class ApiConfiguration
{
    private $uniqueIdentifier = '';
    private $privateKey = '';
    private $apiServerUrl = 'https://paygreen.fr/api';

    /**
     * ApiConfiguration constructor.
     * @param $uniqueIdentifier
     * @param $privateKey
     * @param string $apiHost (optional)
     */
    public function __construct($uniqueIdentifier, $privateKey, $apiHost = '')
    {
        $this->setApiServerUrl($apiHost);
        $this->setUniqueIdentifier($uniqueIdentifier);
        $this->setPrivateKey($privateKey);
    }

    /**
     * @return string
     */
    public function getUniqueIdentifier(): string
    {
        $stripedUniqueIdentifier = $this->uniqueIdentifier;
        if (substr($this->uniqueIdentifier, 0, 2) == 'PP') {
            $stripedUniqueIdentifier = substr($this->uniqueIdentifier, 2);
        }
        return $stripedUniqueIdentifier;
    }

    /**
     * @return string
     */
    public function getApiServerUrl(): string
    {
        return $this->apiServerUrl;
    }

    /**
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    private function setApiServerUrl($apiHost): void
    {
        if (!empty($apiHost)) {
            $this->apiServerUrl = $apiHost . '/api';
        }
    }

    private function setUniqueIdentifier($uniqueIdentifier): void
    {
        if (empty($uniqueIdentifier)) {
            throw new InvalidArgumentException("Missing UniqueIdentifier");
        }

        $this->uniqueIdentifier = $uniqueIdentifier;
    }

    private function setPrivateKey($privateKey): void
    {
        if (empty($privateKey)) {
            throw new InvalidArgumentException("Missing PrivateKey");
        }

        $this->privateKey = $privateKey;
    }
}
