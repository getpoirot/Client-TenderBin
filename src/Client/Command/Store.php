<?php
namespace Poirot\TenderBinClient\Client\Command;

use Poirot\ApiClient\Interfaces\Request\iApiCommand;
use Poirot\ApiClient\Request\tCommandHelper;
use Poirot\Std\Hydrator\HydrateGetters;


class Store
    implements iApiCommand
    , \IteratorAggregate
{
    use tCommandHelper;
    use tTokenAware;

    protected $content;
    protected $contentType;
    protected $title;
    protected $meta = [];
    protected $protected;
    protected $expiration;


    /**
     * MetaInfo constructor.
     *
     * @param string|resource $content
     * @param string          $content_type
     * @param string          $title
     * @param array           $meta
     * @param bool            $protected
     * @param int             $expiration    Timestamp
     */
    function __construct(
        $content
        , $content_type
        , $title = null
        , array $meta = []
        , $protected = true
        , $expiration = null
    )
    {
        $this->content = $content;
        $this->contentType = $content_type;
        $this->title = $title;
        $this->meta = $meta;
        $this->protected = $protected;
        $this->expiration = $expiration;
    }


    // Options:

    /**
     * @return resource|string
     */
    function getContent()
    {
        return $this->content;
    }

    /**
     * @return string
     */
    function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @return null|string
     */
    function getTitle()
    {
        return $this->title;
    }

    /**
     * @return array
     */
    function getMeta()
    {
        return $this->meta;
    }

    /**
     * @return boolean
     */
    function isProtected()
    {
        return $this->protected;
    }

    /**
     * @return string Timestamp
     */
    function getExpiration()
    {
        return $this->expiration;
    }


    // ..

    /**
     * @ignore
     */
    function getIterator()
    {
        $hyd = new HydrateGetters($this);
        $hyd->setExcludeNullValues();
        return $hyd;
    }
}
