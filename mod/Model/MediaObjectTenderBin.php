<?php
namespace Module\TenderBinClient\Model;


class MediaObjectTenderBin
    extends \Poirot\TenderBinClient\Model\MediaObjectTenderBin
{
    /**
     * Generate Http Link To Media
     *
     * @return string
     */
    function get_Link()
    {
        return (string) \Module\Foundation\Actions::Path(
            'tenderbin-media_cdn' // this name is reserved; @see mod-tenderbin_client.conf.php
            , [
                'hash' => $this->getHash()
            ]
        );
    }
}
