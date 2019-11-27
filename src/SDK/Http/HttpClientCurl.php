<?php

namespace Paygreen\SDK\Http;

class HttpClientCurl implements IHttpClient
{
    public function __construct()
    {
    }

    public function callRequest(HttpApiRequest $request)
    {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            // CURLOPT_SSL_VERIFYPEER => false,
            // CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_URL => $request->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $request->httpVerb,
            CURLOPT_POSTFIELDS => $request->content,
            CURLOPT_HTTPHEADER => $request->headers,
        ));
        $page = curl_exec($ch);
        curl_close($ch);

        return ($page);
    }
}
