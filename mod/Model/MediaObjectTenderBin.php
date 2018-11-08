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
        $link = (string) \Module\Foundation\Actions::path(
            'tenderbin-media_cdn' // this name is reserved; @see mod-tenderbin_client.conf.php
            , [
                'hash' => $this->getHash()
            ]
        );

        if (! $filename = $this->getMeta()->get('filename') )
            return $link;


        return sprintf(rtrim($link, '/').'/%s', $filename);
    }
}
