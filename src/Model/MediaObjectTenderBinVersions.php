<?php
namespace Poirot\TenderBinClient\Model;


use Poirot\Std\Hydrator\HydrateGetters;


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

            $r[$ver] = $link."?ver=$ver";
        }

        return $r;
    }
}
