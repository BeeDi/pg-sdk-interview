<?php

namespace Paygreen\SDK\Http;

class HttpClientStream implements IHttpClient
{
    public function callRequest(HttpApiRequest $request)
    {
        $opts = array(
            'http' => array(
                'method'    =>  $request->httpVerb,
                'header'    =>  $this->buildHeaders($request->headers),
                'content'   =>  $request->content
            )
        );
        $context = stream_context_create($opts);
        $page = @file_get_contents($request->url, false, $context);

        return ($page);
    }

    private function buildHeaders(array $headers) : string
    {
        return implode("\r\n", $headers);
    }
}
