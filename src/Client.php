<?php
namespace Poirot\TenderBinClient;

use Poirot\TenderBinClient\Client\Command;
use Poirot\ApiClient\aClient;
use Poirot\ApiClient\Interfaces\iPlatform;
use Poirot\ApiClient\Interfaces\iTokenProvider;
use Poirot\ApiClient\Interfaces\Request\iApiCommand;
use Poirot\TenderBinClient\Client\PlatformRest;
use Poirot\TenderBinClient\Exceptions\exTokenMismatch;


class Client
    extends aClient
{
    protected $baseUrl;
    protected $platform;
    protected $tokenProvider;


    /**
     * TenderBin Client constructor.
     *
     * @param string         $baseUrl
     * @param iTokenProvider $tokenProvider
     */
    function __construct($baseUrl, iTokenProvider $tokenProvider)
    {
        $this->baseUrl  = rtrim( (string) $baseUrl, '/' );
        $this->tokenProvider = $tokenProvider;
    }


    /**
     * Returns metadata about a single bin.
     *
     * @param string $resourceHash
     *
     * @return array
     */
    function getBinMeta($resourceHash)
    {
        $response = $this->call( new Command\MetaInfo($resourceHash) );
        if ( $ex = $response->hasException() )
            throw $ex;

        $r = $response->expected();
        $r = $r->get('result');
        return $r;
    }


    // Implement aClient

    /**
     * Get Client Platform
     *
     * - used by request to build params for
     *   server execution call and response
     *
     * @return iPlatform
     */
    protected function platform()
    {
        if (! $this->platform )
            $this->platform = new PlatformRest;


        # Default Options Overriding
        $this->platform->setServerUrl( $this->baseUrl );

        return $this->platform;
    }


    // ..

    /**
     * @override handle token renewal from server
     *
     * @inheritdoc
     */
    protected function call(iApiCommand $command)
    {
        $recall = 1;

        recall:

        if (method_exists($command, 'setToken')) {
            $token = $this->tokenProvider->getToken();
            $command->setToken($token);
        }


        $platform = $this->platform();
        $platform = $platform->withCommand($command);
        $response = $platform->send();

        if ($ex = $response->hasException()) {

            if ( $ex instanceof exTokenMismatch && $recall > 0 ) {
                // Token revoked or mismatch
                // Refresh Token
                $this->tokenProvider->exchangeToken();
                $recall--;

                goto recall;
            }

            throw $ex;
        }


        return $response;
    }
}
