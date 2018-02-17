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
    function get_Link()
    {
        throw new \RuntimeException('Not Implemented.');
    }
}
