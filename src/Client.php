<?php
namespace Poirot\TenderBinClient;

use Poirot\ApiClient\Interfaces\Token\iTokenProvider;
use Poirot\TenderBinClient\Client\Command;
use Poirot\ApiClient\aClient;
use Poirot\ApiClient\Interfaces\iPlatform;
use Poirot\ApiClient\Interfaces\Request\iApiCommand;
use Poirot\TenderBinClient\Client\PlatformRest;
use Poirot\TenderBinClient\Exceptions\exResourceForbidden;
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
     * Store Bin Data In Storage
     *
     * [code:]
     * $r = $c->store(
     *  serialize($c)
     *  , 'application/php-serialized'
     *  , 'TenderBin Client'
     *  , [
     *  'is_file' => true, // force store as a file
     *  ]
     * );
     * [/code]
     *
     * @param string|resource $content
     * @param string          $content_type
     * @param string          $title
     * @param array           $meta
     * @param bool            $protected
     * @param \DateTime       $expiration
     *
     * @return array
     */
    function store(
        $content
        , $content_type = null
        , $title = null
        , array $meta = []
        , $protected = true
        , \DateTime $expiration = null
    ) {
        $response = $this->call(
            new Command\Store($content, $content_type, $title, $meta, $protected, $expiration)
        );

        if ( $ex = $response->hasException() )
            throw $ex;

        $r = $response->expected();
        $r = $r->get('result');
        return $r;
    }

    /**
     * Returns metadata about a single bin.
     *
     * @param string $resourceHash
     *
     * @return array
     * @throws exResourceNotFound|exResourceForbidden
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
     * @return array [ resource, headers[] ]
     * @throws exResourceNotFound|exResourceForbidden
     */
    function loadBin($resourceHash, $rangeFrom = null, $rangeTo = null)
    {
        $metaInfo = $this->getBinMeta($resourceHash);
        $resource = $this->fetch($resourceHash, $rangeFrom, $rangeTo);

        return [ $resource, $metaInfo['bindata'] ];
    }

    /**
     * Delete Bin and All SubVersions From Storage
     *
     * @param string $resourceHash
     *
     * @return array
     * @throws \Exception
     */
    function delete($resourceHash)
    {
        $response = $this->call( new Command\Delete($resourceHash) );
        if ( $ex = $response->hasException() )
            throw $ex;

        return $response->expected();
    }

    /**
     * Touch File Expiration To Infinite Time
     *
     * @param string $resourceHash
     *
     * @return array
     * @throws \Exception
     */
    function touch($resourceHash)
    {
        $response = $this->call( new Command\Touch($resourceHash) );
        if ( $ex = $response->hasException() )
            throw $ex;

        return $response->expected();
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
     * @throws exResourceNotFound|exResourceForbidden
     */
    function fetch($resourceHash, $rangeFrom = null, $rangeTo = null)
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
