<?php
namespace Module\TenderBinClient\Handler;

use Module\TenderBinClient\Interfaces\iMediaHandler;
use Module\TenderBinClient\Model\MediaObjectTenderBin;
use Poirot\TenderBinClient\Client;
use Poirot\TenderBinClient\Model\aMediaObject;


class HandleTenderBin
    implements iMediaHandler
{
    /**
     * Handler Can Handle Media Object By Storage Type
     *
     * @param string $storageType
     *
     * @return bool
     */
    function canHandleMedia($storageType)
    {
        return ($storageType == 'tenderbin');
    }

    /**
     * Create new Media Object With Given Options
     *
     * @param array $mediaOptions
     *
     * @return aMediaObject
     */
    function newMediaObject(array $mediaOptions)
    {
        $objectMedia = new MediaObjectTenderBin;
        $objectMedia->with( $objectMedia::parseWith($mediaOptions) );

        return$objectMedia;
    }

    /**
     * Client
     *
     * @return Client
     */
    function client()
    {
        return $cTender = \Module\TenderBinClient\Services::ClientTender();
    }
}
