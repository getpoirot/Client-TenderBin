<?php
use Module\HttpFoundation\Actions\Url;

return [

    # Tender Bin Client Paths URL
    #
    // Used To Render Media Contents URL
    /** @see embedLinkToMediaContents() */
    \Module\Foundation\Services\PathService::CONF => [
        'paths' => [
            // According to route name 'www-assets' to serve statics files
            // @see cor-http_foundation.routes
            'mod-content-media_cdn' => function($args) {
                $uri = $this->assemble('$serverUrlTenderBin', $args);
                return $uri;
            },
        ],
        'variables' => [
            'serverUrlTenderBin' => function() {
                return \Module\HttpFoundation\Actions::url(
                    'main/tenderbin/resource/get'
                    , [ 'resource_hash' => '$hash' ]
                    , Url::INSTRUCT_NOTHING | Url::ABSOLUTE_URL
                    #, [ 'absolute_url' => ['server_url' => 'http://storage.apanaj.ir'] ]
                );
            },
        ],
    ],

];
