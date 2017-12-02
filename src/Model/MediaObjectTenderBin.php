<?php
namespace Poirot\TenderBinClient\Model;


class MediaObjectTenderBin
    extends aMediaObject
{
    const TYPE = 'tenderbin';


    /**
     * Generate Http Link To Media
     *
     * @return string
     */
    function getLink()
    {
        throw new \RuntimeException('Not Implemented.');
    }
}
