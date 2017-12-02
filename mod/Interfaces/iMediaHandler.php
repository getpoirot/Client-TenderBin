<?php
namespace Module\TenderBinClient\Interfaces;

use Poirot\TenderBinClient\Client;
use Poirot\TenderBinClient\Model\aMediaObject;


interface iMediaHandler
{
    /**
     * Handler Can Handle Media Object By Storage Type
     *
     * @param string $storageType
     *
     * @return bool
     */
    function canHandleMedia($storageType);

    /**
     * Create new Media Object With Given Options
     *
     * @param array $mediaOptions
     *
     * @return aMediaObject
     */
    function newMediaObject(array $mediaOptions);

    /**
     * Client
     *
     * @return Client
     */
    function client();
}
