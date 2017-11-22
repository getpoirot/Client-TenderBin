<?php

return [

    # Tender Bin Client Paths URL
    #
    // Used To Render Media Contents URL
    /** @see embedLinkToMediaContents() */
    \Module\Foundation\Services\PathService::CONF => [
        'paths' => [
            // According to route name 'www-assets' to serve statics files
            // @see cor-http_foundation.routes
            'tenderbin-media_cdn' => function($args) {
                $uri = $this->assemble('$serverUrlTenderBin', $args);
                return $uri;
            },
        ],
        'variables' => [
            'serverUrlTenderBin' => function() {
                // Server Url From Client TenderBin
                $c = \Module\TenderBinClient\Services::ClientTender();
                $serverUrl = rtrim($c->getServerUrl(), '/');
                return $serverUrl.'/$hash';
            },

            /*
            'serverUrlTenderBin' => function() {
                // Server Url From Server Url
                return \Module\HttpFoundation\Actions::url(
                    'main/tenderbin/resource/get'
                    , [ 'resource_hash' => '$hash' ]
                    , Url::INSTRUCT_NOTHING | Url::ABSOLUTE_URL
                    #, [ 'absolute_url' => ['server_url' => 'http://storage.apanaj.ir'] ]
                );
            },
            */
        ],
    ],

];
