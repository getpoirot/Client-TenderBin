<?php
namespace Poirot\TenderBinClient\Model;

use Poirot\Std\Interfaces\Struct\iData;
use Poirot\Std\Struct\aValueObject;
use Poirot\Std\Struct\DataEntity;
use Poirot\TenderBinClient\FactoryMediaObject;


abstract class aMediaObject
    extends aValueObject
{
    const TYPE = 'not_known';

    /** @var array */
    protected $hash;
    protected $contentType;
    protected $meta;
    protected $versions;


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
            throw new \Exception(sprintf(
                'Mismatch Storage Type (%s) For (%s).'
                , $storageType
                , $this->getStorageType()
            ));

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

    /**
     * Set Meta Data
     *
     * note: clean current data
     *
     * @param \Traversable|array $metaData
     *
     * @return $this
     */
    function setMeta($metaData)
    {
        $meta = clone $this->getMeta();
        $meta->clean()->import($metaData);

        $this->meta = $meta;
        return $this;
    }

    /**
     * Meta Information Of Bin
     *
     * @return iData
     */
    function getMeta()
    {
        if (! $this->meta )
            $this->meta = new DataEntity;

        return $this->meta;
    }

    /**
     * Set Versions
     *
     * @param []aMediaObject $versions
     *
     * @return $this
     */
    function setVersions($versions)
    {
        if ($versions) {
            foreach ($versions as $v => $media)
                $this->addVersion($v, $media);
        }

        return $this;
    }

    function addVersion($v, aMediaObject $media)
    {
        $this->versions[(string)$v] = $media;
        return $this;
    }

    /**
     * Get SubVersions Available of this media
     *
     * @return array|null
     */
    function getVersions()
    {
        return $this->versions;
    }


    // ..

    function with(array $options, $throwException = false)
    {
        if (isset($options['versions'])) {
            if (! is_array($options['versions']) )
                $options['versions'] = iterator_to_array($options['versions']);

            foreach ($options['versions'] as $verName => $media) {
                if (! $media instanceof aMediaObject)
                    $options['versions'][$verName] = FactoryMediaObject::of($media);
            }

        }

        parent::with($options, $throwException);
    }
}
