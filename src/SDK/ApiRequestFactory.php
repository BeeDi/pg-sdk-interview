<?php

namespace Paygreen\SDK;

use InvalidArgumentException;
use Paygreen\SDK\Http\HttpApiRequest;

class ApiRequestFactory
{
    /**
     * @var ApiConfiguration
     */
    private $configuration;

    public function __construct(ApiConfiguration $configuration)
    {
        if (!isset($configuration)) {
            throw new InvalidArgumentException("Invalid API configuration");
        }
        $this->configuration = $configuration;
    }

    /**
     * @param ApiConfiguration $configuration
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
     * @param string $endpointKey
     * @param string $endpointValue
     * @param array|null $content
     * @return HttpApiRequest
     */
    public function create(string $endpointKey, string $endpointValue = '', array $content = null): HttpApiRequest
    {
        $req = new HttpApiRequest(
            $this->configuration->getPrivateKey(),
            ApiEndpoint::getEndpointVerb($endpointKey),
            $this->buildUrl($this->configuration, ApiEndpoint::getEndpointFormat($endpointKey), $endpointValue)
        );

        if (isset($content)) {
            $req->addContent($content);
        }

        return $req;
    }
}
