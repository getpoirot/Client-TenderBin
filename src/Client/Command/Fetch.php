<?php
namespace Poirot\TenderBinClient\Client\Command;

use Poirot\ApiClient\Interfaces\Request\iApiCommand;
use Poirot\ApiClient\Request\tCommandHelper;


class Fetch
    implements iApiCommand
    , \IteratorAggregate
{
    use tCommandHelper;
    use tTokenAware;

    protected $resourceHash;
    protected $range;


    /**
     * MetaInfo constructor.
     *
     * @param string $resourceHash
     * @param int    $rangeFrom
     * @param int    $rangeTo
     */
    function __construct($resourceHash, $rangeFrom = null, $rangeTo = null)
    {
        $this->resourceHash = $resourceHash;

        if (isset($rangeFrom) || isset($rangeTo)) {
            $this->range = [
                $rangeFrom,
                $rangeTo
            ];
        }
    }


    // Options

    /**
     * Get ResourceHash
     *
     * @return mixed
     */
    function getResourceHash()
    {
        return $this->resourceHash;
    }

    /**
     * Get Range
     *
     * @return array
     */
    function getRange()
    {
        return $this->range;
    }


    // ..

    function getIterator()
    {
        return new \ArrayIterator([
            'resource_hash' => $this->getResourceHash()
        ]);
    }
}
