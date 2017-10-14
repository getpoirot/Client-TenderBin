<?php
namespace Poirot\TenderBinClient\Client;

use Poirot\ApiClient\Exceptions\exHttpResponse;
use Poirot\ApiClient\Response\ExpectedJson;
use Poirot\ApiClient\ResponseOfClient;
use Poirot\TenderBinClient\Exceptions\exResourceForbidden;
use Poirot\TenderBinClient\Exceptions\exResourceNotFound;
use Poirot\TenderBinClient\Exceptions\exServerError;
use Poirot\TenderBinClient\Exceptions\exUnexpectedValue;
use Poirot\TenderBinClient\Exceptions\exTokenMismatch;


class Response
    extends ResponseOfClient
{
    /**
     * Has Exception?
     *
     * @return \Exception|false
     */
    function hasException()
    {
        if ($this->exception instanceof exHttpResponse) {
            // Determine Known Errors ...
            $expected = $this->expected();
            if ($expected && $err =  $expected->get('error') ) {
                switch ($err['state']) {
                    case 'exResourceNotFound':
                        $this->exception = new exResourceNotFound($err['message'], (int) $err['code']);
                        break;
                    case 'exAccessDenied':
                        $this->exception = new exResourceForbidden($err['message'], (int) $err['code']);
                        break;
                    case 'exOAuthAccessDenied':
                        $this->exception = new exTokenMismatch($err['message'], (int) $err['code']);
                        break;    
                    case 'exUnexpectedValue':
                        $this->exception = new exUnexpectedValue($err['message'], (int) $err['code']);
                        break;
                }
            }
        }


        return $this->exception;
    }

    /**
     * Process Raw Body As Result
     *
     * :proc
     * mixed function($originResult, $self);
     *
     * @param callable $callable
     *
     * @return mixed
     */
    function expected(/*callable*/ $callable = null)
    {
        if ( $callable === null )
            // Retrieve Json Parsed Data Result
            $callable = $this->_getDataParser();


        return parent::expected($callable);
    }


    // ...

    function _getDataParser()
    {
        if ( false !== strpos($this->getMeta('content_type'), 'application/json') )
            // Retrieve Json Parsed Data Result
            return new ExpectedJson;


        if ($this->responseCode == 204) {
            return function() {
                return null;
            };
        }

        throw new exServerError($this->rawBody);
    }
}
