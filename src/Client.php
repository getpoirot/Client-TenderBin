<?php
namespace Poirot\TenderBinClient;

use Poirot\ApiClient\Interfaces\Token\iTokenProvider;
use Poirot\Std\Interfaces\Struct\iDataEntity;
use Poirot\TenderBinClient\Client\Command;
use Poirot\ApiClient\aClient;
use Poirot\ApiClient\Interfaces\iPlatform;
use Poirot\ApiClient\Interfaces\Request\iApiCommand;
use Poirot\TenderBinClient\Client\PlatformRest;
use Poirot\TenderBinClient\Client\Response;
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

/*
Stored Bin Data Object:
Array
(
    [bindata] => Array
        (
            [hash] => 599ffc2fbf2bc35359595d12
            [title] => 427575.jpg
            [content_type] => image/jpeg
            [expire_in] =>
            [is_protected] =>
            [meta] => Array
                (
                    [is_file] => 1
                    [filename] => phpBroPKy
                    [filesize] => 47570
                    [md5] => 5bfcc2995ce730edb229feed87cb2c59
                )

            [version] => Array
                (
                    [subversion_of] =>
                    [tag] => latest
                )

        )

    [_link] => /599ffc2fbf2bc35359595d12/phpBroPKy
)
*/

class Client
    extends aClient
{
    protected $serverUrl;
    protected $platform;
    protected $tokenProvider;


    /**
     * TenderBin Client constructor.
     *
     * @param string         $serverUrl
     * @param iTokenProvider $tokenProvider
     */
    function __construct($serverUrl, iTokenProvider $tokenProvider)
    {
        $this->serverUrl  = rtrim( (string) $serverUrl, '/' );
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
     * @param int             $expiration    Timestamp
     * @param bool            $protected
     *
     * @return array
     */
    function store(
        $content
        , $content_type = null
        , $title = null
        , array $meta = []
        , $expiration = null
        , $protected = true
    ) {
        $response = $this->call(
            new Command\Store($content, $content_type, $title, $meta, $protected, $expiration)
        );

        if ( $ex = $response->hasException() )
            throw $ex;

        $r = $response->expected();
        $r = ($r instanceof iDataEntity) ? $r->get('result') : $r;
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
        $r = ($r instanceof iDataEntity) ? $r->get('result') : $r;
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

        // TODO fetch response
        return $response->expected();
    }


    // Options

    /**
     * Set Token Provider
     *
     * @param iTokenProvider $tokenProvider
     *
     * @return $this
     */
    function setTokenProvider(iTokenProvider $tokenProvider)
    {
        $this->tokenProvider = $tokenProvider;
        return $this;
    }

    /**
     * Server Url
     *
     * @return string
     */
    function getServerUrl()
    {
        return $this->serverUrl;
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
        $this->platform->setServerUrl( $this->serverUrl );

        return $this->platform;
    }


    // ..

    /**
     * @override handle token renewal from server
     *
     * @inheritdoc
     *
     * @return Response
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
                // TODO Handle Errors while retrieve token (delete cache)
                $this->tokenProvider->exchangeToken();
                $recall--;

                goto recall;
            }

            throw $ex;
        }


        return $response;
    }
}
