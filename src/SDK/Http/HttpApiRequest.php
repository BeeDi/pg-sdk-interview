<?php

namespace Paygreen\SDK\Http;

class HttpApiRequest
{
    public $url;
    public $httpVerb;
    public $content = '';
    public $headers;

    public function __construct(
        string $privateKey,
        string $httpVerb,
        string $url
    ) {
        $this->url = $url;
        $this->headers = array(
            $this->buildAuthorizationHeader($privateKey),
            "accept: application/json",
            "cache-control: no-cache",
            "content-type: application/json"
        );
        $this->httpVerb = $httpVerb;
    }

    public function addContent($content)
    {
        if (isset($content)) {
            $this->content = json_encode($content);
        }
    }

    /**
     * @param string $privateKey
     * @return string
     */
    private function buildAuthorizationHeader(string $privateKey): string
    {
        return "Authorization: Bearer " . $privateKey;
    }
}
