<?php
namespace Poirot\TenderBinClient\Client;

use Poirot\ApiClient\aPlatform;
use Poirot\ApiClient\Exceptions\exConnection;
use Poirot\ApiClient\Exceptions\exHttpResponse;
use Poirot\ApiClient\Interfaces\iPlatform;
use Poirot\ApiClient\Interfaces\Request\iApiCommand;
use Poirot\ApiClient\Interfaces\Response\iResponse;
use Poirot\Http\Header\CollectionHeader;
use Poirot\Http\Header\FactoryHttpHeader;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Std\ErrorStack;
use Poirot\Std\Type\StdArray;
use Poirot\TenderBinClient\Client\PlatformRest\ServerUrlEndpoints;
use Poirot\TenderBinClient\Exceptions\exResourceForbidden;
use Poirot\TenderBinClient\Exceptions\exResourceNotFound;


class PlatformRest
    extends aPlatform
    implements iPlatform
{
    /** @var iApiCommand */
    protected $Command;

    // Options:
    protected $usingSsl  = false;
    protected $serverUrl = null;


    // Alters

    /**
     * @param Command\Store $command
     * @return iResponse
     */
    protected function _Store(Command\Store $command)
    {
        $headers = [];

        // Request With Client Credential
        // As Authorization Header
        $headers['Authorization'] = 'Bearer '. ( $command->getToken()->getAccessToken() );

        $args = iterator_to_array($command);

       if ( is_resource($command->getContent()) ) {
            // For now convert stream that considered file into uri and post content with curl
            $fMeta = stream_get_meta_data($command->getContent());

            $args['content'] = new \CURLFile( $fMeta['uri'], $this->_getMimeTypeFromResource($fMeta) );
        }


        $url = $this->_getServerUrlEndpoints($command);
        $response = $this->_sendViaCurl('POST', $url, $args, $headers);
        return $response;
    }

    /**
     * @param Command\Delete $command
     * @return iResponse
     */
    protected function _Delete(Command\Delete $command)
    {
        $headers = [];

        // Request With Client Credential
        // As Authorization Header
        $headers['Authorization'] = 'Bearer '. ( $command->getToken()->getAccessToken() );


        $url = $this->_getServerUrlEndpoints($command);
        $response = $this->_sendViaCurl('DELETE', $url, [], $headers);
        return $response;
    }

    /**
     * @param Command\Touch $command
     * @return iResponse
     */
    protected function _Touch(Command\Touch $command)
    {
        $headers = [];

        // Request With Client Credential
        // As Authorization Header
        $headers['Authorization'] = 'Bearer '. ( $command->getToken()->getAccessToken() );


        $url = $this->_getServerUrlEndpoints($command);
        $response = $this->_sendViaCurl('PUT', $url, ['expiration' => 0], $headers);
        return $response;
    }

    /**
     * @param Command\MetaInfo $command
     * @return iResponse
     */
    protected function _MetaInfo(Command\MetaInfo $command)
    {
        $headers = [];

        // Request With Client Credential
        // As Authorization Header
        $headers['Authorization'] = 'Bearer '. ( $command->getToken()->getAccessToken() );


        $url = $this->_getServerUrlEndpoints($command);
        $response = $this->_sendViaCurl('GET', $url, [], $headers);
        return $response;
    }

    /**
     * @param Command\ListMetaInfo $command
     * @return iResponse
     */
    protected function _ListMetaInfo(Command\ListMetaInfo $command)
    {
        $headers = [];

        // Request With Client Credential
        // As Authorization Header
        $headers['Authorization'] = 'Bearer '. ( $command->getToken()->getAccessToken() );

        $url = $this->_getServerUrlEndpoints($command);
        $response = $this->_sendViaCurl('POST', $url, ['hashes' => $command->getHashes()], $headers);
        return $response;
    }

    /**
     * @param Command\Fetch $command
     * @return iResponse
     */
    protected function _Fetch(Command\Fetch $command)
    {
        $range = $command->getRange();
        $hash  = $command->getResourceHash();

        $context = null;
        $headers = [];
        // Request With Client Credential
        // As Authorization Header
        $headers['Authorization'] = 'Bearer '. ( $command->getToken()->getAccessToken() );
        (! $range ) ?: $headers['Range'] = 'byte='.implode('-', $range); // byte=0-1500


        if (! empty($headers) ) {
            $h = [];
            foreach ($headers as $key => $val)
                $h[] = $key.': '.$val;
            $headers = $h;

            $opts = [ 'http' => [
                    'header'  => $headers, ] ];
            $context = stream_context_create($opts);
        }


        $url = $this->_getServerUrlEndpoints($command); $exception=null; $code=200;

        ErrorStack::handleError( E_ALL );
        if (false === $response = $file = fopen($url, 'rb', false, $context)) {
            $code      = 400;
            $exception = new exHttpResponse('Error While Retrieve Resource', $code);
        }
        if ( $ex = ErrorStack::handleDone() ) {
            // fopen(http://...): failed to open stream: HTTP request failed! HTTP/1.1 403 Forbidden
            if ( false !== strpos($ex->getMessage(), '403 Forbidden') )
                $exception = new exResourceForbidden;
            if ( false !== strpos($ex->getMessage(), '404 Not Found') )
                $exception = new exResourceNotFound();
        }

        $response = new Response(
            $response
            , $code
            , []
            , $exception
        );

        return $response;
    }


    // Options

    /**
     * Set Server Url
     *
     * @param string $url
     *
     * @return $this
     */
    function setServerUrl($url)
    {
        $this->serverUrl = (string) $url;
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

    /**
     * Using SSl While Send Request To Server
     *
     * @param bool $flag
     *
     * @return $this
     */
    function setUsingSsl($flag = true)
    {
        $this->usingSsl = (bool) $flag;
        return $this;
    }

    /**
     * Ssl Enabled?
     *
     * @return bool
     */
    function isUsingSsl()
    {
        return $this->usingSsl;
    }


    // ..

    protected function _sendViaCurl($method, $url, array $data, array $headers = [])
    {
        if (! extension_loaded('curl') )
            throw new \Exception('cURL library is not loaded');


        $handle = curl_init();

        $h = [];
        foreach ($headers as $key => $val)
            $h[] = $key.': '.$val;
        $headers = $h;


        $defHeaders = [
            'Accept: application/json',
            'charset: utf-8'
        ];

        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);

        if ($method == 'POST' || $method == 'PUT') {
            /*$defHeaders += [
                'Content-Type: application/x-www-form-urlencoded'
            ];*/

            curl_setopt($handle, CURLOPT_POST, true);

            # build request body
            $data = StdArray::of($data)->makeFlattenFace();
            curl_setopt($handle, CURLOPT_POSTFIELDS, $data->value);

        } else {
            $urlEncodeData = http_build_query($data);
            // TODO set data in qcuery params
        }

        $headers = array_merge(
            $defHeaders
            , $headers
        );


        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);


        # Send Post Request
        $cResponse     = curl_exec($handle);
        $cResponseCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $cContentType  = curl_getinfo($handle, CURLINFO_CONTENT_TYPE);

        if ($curl_errno = curl_errno($handle)) {
            // Connection Error
            $curl_error = curl_error($handle);
            throw new exConnection($curl_error, $curl_errno);
        }

        $exception = null;
        if (! ($cResponseCode >= 200 && $cResponseCode < 300) ) {
            $message = $cResponse;
            if ($cResponseCode >= 300 && $cResponseCode < 400)
                $message = 'Response Redirected To Another Uri.';

            $exception = new exHttpResponse($message, $cResponseCode);
        }

        $response = new Response(
            $cResponse
            , $cResponseCode
            , ['content_type' => $cContentType]
            , $exception
        );

        return $response;
    }

    protected function _getServerUrlEndpoints($command)
    {
        $url = new ServerUrlEndpoints(
            $this->getServerUrl()
            , $command
            , $this->isUsingSsl()
        );

        return (string) $url;
    }

    protected function _handleError($e)
    {

    }

    private function _getMimeTypeFromResource (array $fileMetadata)
    {
        if ('http' == $fileMetadata['wrapper_type']) {
            $headers = new CollectionHeader();
            foreach ($fileMetadata['wrapper_data'] as $i => $h) {
                if ($i === 0)
                    // This is request Status Header (GET HTTP 1.1)
                    continue;

                 $headers->insert(FactoryHttpHeader::of($h));
            }

            if (! $headers->has('Content-Type') )
                return '*/*';


            // TODO render header(s) with related function of http
            $value = '';
            /** @var iHeader $h */
            foreach ( $headers->get('Content-Type') as $h)
                $value .= $h->renderValueLine();

            return $value;
        }

        return mime_content_type($fileMetadata['uri']);
    }
}
