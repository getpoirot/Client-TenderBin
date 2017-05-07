<?php
namespace Poirot\TenderBinClient\Client;

use Poirot\ApiClient\Exceptions\exHttpResponse;
use Poirot\ApiClient\Response\ExpectedJson;
use Poirot\ApiClient\ResponseOfClient;


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


        return null;
    }
}
