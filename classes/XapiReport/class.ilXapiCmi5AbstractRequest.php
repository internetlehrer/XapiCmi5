<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilXapiCmi5AbstractRequest
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 */
abstract class ilXapiCmi5AbstractRequest
{
    /**
     * @var string
     */
    private $basicAuth;
    
    /**
     * ilXapiCmi5AbstractRequest constructor.
     * @param string $basicAuth
     */
    public function __construct(string $basicAuth)
    {
        if ((int)ILIAS_VERSION_NUMERIC < 6) { // only in plugin
            require_once __DIR__.'/../XapiProxy/vendor/autoload.php';
        }
        $this->basicAuth = $basicAuth;
    }
    
    /**
     * @param string $url
     * @return string
     */
    protected function sendRequest($url)
    {
        global $DIC;
        $client = new GuzzleHttp\Client();
        $request = new GuzzleHttp\Psr7\Request('GET', $url, [
            'Authorization' => $this->basicAuth,
            'X-Experience-API-Version' => '1.0.3'
        ]);
        try {
            $response = $client->sendAsync($request)->wait();
            return (string) $response->getBody();
        }
        catch(Exception $e) {
            throw new Exception("LRS Connection Problems");
        }
    }
}