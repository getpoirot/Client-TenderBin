<?php
namespace Poirot\TenderBinClient\Client\Command;

use Poirot\ApiClient\Interfaces\Request\iApiCommand;
use Poirot\ApiClient\Request\tCommandHelper;


class ListMetaInfo
    implements iApiCommand
    , \IteratorAggregate
{
    use tCommandHelper;
    use tTokenAware;

    protected $hashes;


    /**
     * MetaInfo constructor.
     *
     * @param $resourceHashes
     */
    function __construct(array $resourceHashes)
    {
        $this->hashes = $resourceHashes;
    }

    /**
     * Get ResourceHashes
     *
     * @return mixed
     */
    function getHashes()
    {
        return $this->hashes;
    }


    // ..

    function getIterator()
    {
        return new \ArrayIterator([
            'resource_hashes' => $this->getHashes()
        ]);
    }
}
