<?php
namespace Poirot\TenderBinClient\Model;

use Poirot\Std\Interfaces\Struct\iData;

class MediaObjectTenderBinVersions
    extends MediaObjectTenderBin
{
    /**
     * @var MediaObjectTenderBin
     */
    public $mediaObject;

    protected $vers;


    /**
     * MediaObjectTenderBinVersions constructor.
     *
     * @param MediaObjectTenderBin $mediaObject
     * @param array                $vers
     */
    function __construct(MediaObjectTenderBin $mediaObject, array $vers)
    {
        $this->mediaObject = $mediaObject;
        $this->vers = $vers;
    }

    /**
     * Determine Used Stored Engine Type
     *
     * @return string
     */
    function getStorageType()
    {
        return $this->mediaObject->getStorageType();
    }

    function getHash()
    {
        return $this->mediaObject->getHash();
    }

    function getContentType()
    {
        return $this->mediaObject->getContentType();
    }

    /**
     * Meta Information Of Bin
     *
     * @return iData
     */
    function getMeta()
    {
        $this->mediaObject->getMeta();
    }

    function addVersion($v, aMediaObject $media)
    {
        throw new \Exception('Not Allowed!!');
    }

    function get_Link()
    {
        $link = $this->mediaObject->get_Link();

        $r = [];
        foreach ($this->vers as $ver)
        {
            if ($ver == 'origin') {
                $r['origin'] = $link;
                continue;
            }

            if ( is_array($ver) ) {
                foreach ($ver['aliases'] as $alias => $of)
                    $r[$alias] = $link."?ver=$of";

                continue;
            }

            $r[$ver] = $link."?ver=$ver";
        }

        return $r;
    }
}
