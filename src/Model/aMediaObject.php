<?php
namespace Poirot\TenderBinClient\Model;

use Poirot\Std\Struct\aValueObject;


class aMediaObject
    extends aValueObject
{
    const TYPE = 'not_known';

    /** @var array */
    protected $hash;
    protected $contentType;


    /**
     * Determine Used Stored Engine Type
     *
     * @return string
     */
    final function getStorageType()
    {
        return static::TYPE;
    }

    final function setStorageType($storageType)
    {
        if ( $storageType !== $this->getStorageType() )
            throw new \Exception(sprintf('Mismatch Storage Type (%s).', $storageType));

    }


    /**
     * Set BinData Hash Object
     *
     * @param string $hash
     *
     * @return $this
     */
    function setHash($hash)
    {
        $this->hash = (string) $hash;
        return $this;
    }

    function getHash()
    {
        return $this->hash;
    }

    /**
     * Media Content Type (Mime)
     * exp. image/jpeg
     *
     * @param string $contentType
     *
     * @return $this
     */
    function setContentType($contentType)
    {
        $this->contentType = (string) $contentType;
        return $this;
    }

    function getContentType()
    {
        return $this->contentType;
    }
}