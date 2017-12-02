<?php
namespace Poirot\TenderBinClient\Model;

use Module\Content\Interfaces\Model\Entity\iEntityMediaObject;
use Poirot\Std\Struct\aValueObject;


abstract class aMediaObject
    extends aValueObject
    implements iEntityMediaObject
{
    const TYPE = 'not_known';

    /** @var array */
    protected $hash;
    protected $contentType;


    /**
     * Generate Http Link To Media
     *
     * @ignore
     *
     * @return string
     */
    abstract function get_Link();


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
