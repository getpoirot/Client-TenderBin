<?php
use Poirot\Ioc\instance;

return [
    'services' => [
        'ClientTender' => new instance(
            \Module\TenderBinClient\Services\ServiceClientTender::class
            , \Poirot\Std\catchIt(function () {
                if (false === $c = \Poirot\Config\load(__DIR__.'/tenderbin/client-tenderbin'))
                    throw new \Exception('Config (tenderbin/client-tenderbin) not loaded.');

                return $c->value;
            })
        ),
    ],
];
