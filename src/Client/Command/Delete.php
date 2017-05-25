<?php
namespace Poirot\TenderBinClient\Client\Command;

use Poirot\ApiClient\Interfaces\Request\iApiCommand;
use Poirot\ApiClient\Request\tCommandHelper;


class Delete
    implements iApiCommand
    , \IteratorAggregate
{
    use tCommandHelper;
    use tTokenAware;

    protected $resourceHash;


    /**
     * Delete constructor.
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


    // ..

    function getIterator()
    {
        return new \ArrayIterator([
            'resource_hash' => $this->getResourceHash()
        ]);
    }
}
