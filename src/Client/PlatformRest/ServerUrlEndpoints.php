<?php
namespace Poirot\TenderBinClient\Client\PlatformRest;

use Poirot\ApiClient\Interfaces\Request\iApiCommand;
use Poirot\OAuth2Client\Federation\Command\Recover\Validate;


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
            case 'register':
                $base = 'api/v1/members';
                break;

            case 'accountinfo':
                /** @var Validate $cmMethod */
                $params = iterator_to_array($command);
                if (isset($params['username']))
                    $postfix = '@'.$params['username'];
                else
                    $postfix = current($params);
                $base = 'api/v1/members/profile/'.$postfix;
                break;


            case 'recover::resendcode':
                /** @var Validate $cmMethod */
                $params = iterator_to_array($command);
                $base = 'recover/validate/resend/'.$params['validation_code'].'/'.$params['identifier_type'];
                break;
        }

        $serverUrl = rtrim($this->serverBaseUrl, '/');
        (! $base ) ?: $serverUrl .= '/'. trim($base, '/');
        return $serverUrl;
    }
}
