<?php
namespace Poirot\TenderBinClient;

use Poirot\ApiClient\Interfaces\Token\iTokenProvider;
use Poirot\TenderBinClient\Client\Command;
use Poirot\ApiClient\aClient;
use Poirot\ApiClient\Interfaces\iPlatform;
use Poirot\ApiClient\Interfaces\Request\iApiCommand;
use Poirot\TenderBinClient\Client\PlatformRest;
use Poirot\TenderBinClient\Exceptions\exResourceNotFound;
use Poirot\TenderBinClient\Exceptions\exTokenMismatch;


/*

$c = new \Poirot\TenderBinClient\Client(
    'http://172.17.0.1:8080/bin'
    , new \Poirot\ApiClient\TokenProviderSolid(
        new \Poirot\ApiClient\AccessTokenObject([
            'access_token' => '#accesstoken',
            'client_id'    => '#clientid',
            'expires_in'   => 3600,
            'scopes'       => 'scope otherscope'
        ])
    )
);

$resource = $c->getBinMeta('58eca65857077400155a1bd2');

*/

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
     * @throws exResourceNotFound
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

    /**
     * Load BinData Content Into Local Stream
     *
     * [code:]
     * list($resource, $meta) = $c->loadBin('58eca65857077400155a1bd2');
     * header('Content-Type: '. $meta['content_type']);
     * while ($content = fread($resource, 2048)) { // Read in 2048-byte chunks
     *  echo $content; // or output it somehow else.
     *  flush(); // force output so far
     * }
     * [/code]
     *
     * @param string $resourceHash
     * @param int    $rangeFrom
     * @param int    $rangeTo      If RangeFrom Not Given Load x byte From End
     *
     * @return array [ headers[], resource ]
     */
    function loadBin($resourceHash, $rangeFrom, $rangeTo)
    {
        $metaInfo = $this->getBinMeta($resourceHash);
        $resource = $this->fetch($resourceHash, $rangeFrom, $rangeTo);

        return [ $resource, $metaInfo['bindata'] ];
    }

    /**
     * Fetch BinData Content Into Local Stream
     *
     * [code:]
     * $resource = $c->fetch('58eca65857077400155a1bd2');
     * while ($content = fread($resource, 2048)) { // Read in 2048-byte chunks
     *  echo $content; // or output it somehow else.
     *  flush(); // force output so far
     * }
     * [/code]
     *
     * @param string $resourceHash
     * @param int    $rangeFrom
     * @param int    $rangeTo      If RangeFrom Not Given Load x byte From End
     *
     * @return array [ headers[], resource ]
     */
    function fetch($resourceHash, $rangeFrom, $rangeTo)
    {
        $response = $this->call( new Command\Fetch($resourceHash, $rangeFrom, $rangeTo) );
        if ( $ex = $response->hasException() )
            throw $ex;

        return $response->expected();
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


        $response = parent::call($command);

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