<?php
namespace Poirot\TenderBinClient\Client\PlatformRest;

use Poirot\ApiClient\Interfaces\Request\iApiCommand;
use Poirot\OAuth2Client\Federation\Command\Recover\Validate;
use Poirot\TenderBinClient\Client\Command\Fetch;
use Poirot\TenderBinClient\Client\Command\MetaInfo;


class ServerUrlEndpoints
{
    protected $serverBaseUrl;
    protected $command;


    /**
     * ServerUrlEndpoints constructor.
     *
     * @param $serverBaseUrl
     * @param $command
     */
    function __construct($serverBaseUrl, $command, $ssl = false)
    {
        $this->serverBaseUrl = (string) $serverBaseUrl;
        $this->command    = $command;
    }

    function __toString()
    {
        return $this->_getServerHttpUrlFromCommand($this->command);
    }


    // ..

    /**
     * Determine Server Http Url Using Http or Https?
     *
     * @param iApiCommand $command
     *
     * @return string
     * @throws \Exception
     */
    protected function _getServerHttpUrlFromCommand($command)
    {
        $base = null;

        $cmMethod = strtolower( (string) $command );
        switch ($cmMethod) {
            case 'store':
                /** @var Fetch $command */
                $params = iterator_to_array($command);
                $base = isset($params['resource_hash']) ? $params['resource_hash'] : '';
                break;
            case 'fetch':
                /** @var Fetch $command */
                $params = iterator_to_array($command);
                $base = $params['resource_hash'];
                break;
            case 'metainfo':
                /** @var MetaInfo $command */
                $params = iterator_to_array($command);
                $base = $params['resource_hash'].'/meta';
                break;
        }

        $serverUrl = rtrim($this->serverBaseUrl, '/');
        (! $base ) ?: $serverUrl .= '/'. trim($base, '/');
        return $serverUrl;
    }
}
