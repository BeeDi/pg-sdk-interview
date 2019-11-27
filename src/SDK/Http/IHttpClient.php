<?php

namespace Paygreen\SDK\Http;

interface IHttpClient
{
    public function callRequest(HttpApiRequest $request);
}
