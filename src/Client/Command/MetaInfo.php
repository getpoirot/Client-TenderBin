<?php
namespace Poirot\TenderBinClient\Client\Command;

use Poirot\ApiClient\Interfaces\Request\iApiCommand;
use Poirot\ApiClient\Request\tCommandHelper;


class MetaInfo
    implements iApiCommand
{
    use tCommandHelper;

    protected $resourceHash;


    /**
     * MetaInfo constructor.
     *
     * @param $resourceHash
     */
    function __construct($resourceHash)
    {
        $this->resourceHash = $resourceHash;
    }

    /**
     * Get ResourceHash
     *
     * @return mixed
     */
    function getResourceHash()
    {
        return $this->resourceHash;
    }
}
