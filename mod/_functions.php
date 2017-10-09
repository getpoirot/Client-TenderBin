<?php
namespace Poirot\TenderBinClient
{
    use Poirot\Std\Interfaces\Pact\ipFactory;
    use Poirot\Std\Type\StdArray;
    use Poirot\Std\Type\StdTravers;
    use Poirot\TenderBinClient\Exceptions\exResourceNotFound;
    use Poirot\TenderBinClient\Model\MediaObjectTenderBin;


    /**
     * Magic Touch Media Contents To Infinite Expiration
     *
     * @param \Traversable|array $content
     *
     * @throws \Exception
     */
    function assertMediaContents($content)
    {
        if (! $content instanceof \Traversable )
            // Do Nothing!!
            return;


        /** @var $cTender */
        $cTender = \Module\TenderBinClient\Services::ClientTender();

        foreach ($content as $c)
        {
            if ($c instanceof MediaObjectTenderBin) {
                try {
                    $cTender->touch( $c->getHash() );
                } catch (exResourceNotFound $e) {
                    // Specific Content Client Exception
                } catch (\Exception $e) {
                    // Other Errors Throw To Next Layer!
                    throw $e;
                }
            }

            elseif (is_array($c) || $c instanceof \Traversable)
                assertMediaContents($c);
        }
    }

    /**
     * Embed Retrieval Http Link To MediaObjects
     *
     * @param array|\Traversable $content Content include MediaObject
     *
     * @return array Prepared content include link(s)
     * @throws \Exception
     */
    function embedLinkToMediaData($content)
    {
        if ($content instanceof \Traversable )
            $content = StdTravers::of($content)->toArray();

        if (! is_array($content) )
            throw new \Exception('Medias is not an type of array.');


        $content = StdArray::of($content)->withWalk(function(&$val) {
            if (! $val instanceof MediaObjectTenderBin )
                throw new \Exception(sprintf(
                    'Unknown Object Of Type (%s).'
                    , \Poirot\Std\flatten($val)
                ));


            $orig         = $val;
            $val          = StdTravers::of($val)->toArray();
            $val['_link'] = (string) \Module\Foundation\Actions::Path(
                'tenderbin-media_cdn' // this name is reserved; @see mod-content.conf.php
                , [
                    'hash' => $orig->getHash()
                ]
            );
        });

        return $content->value; // instance access to internal array
    }


    class FactoryMediaObject
        implements ipFactory
    {
        /**
         * Factory With Valuable Parameter
         *
         * @param null  $mediaData
         *
         * @return MediaObjectTenderBin
         * @throws \Exception
         */
        static function of($mediaData = null)
        {
            // Content Object May Fetch From DB Or Sent By Post Http Request

            /*
            {
                "storage_type": "tenderbin",
                "hash": "58c7dcb239288f0012569ed0",
                "content_type": "image/jpeg"
            }
            */

            if ($mediaData instanceof \Traversable)
                $mediaData = iterator_to_array($mediaData);


            if (! isset($mediaData['storage_type']) )
                $mediaData['storage_type'] = 'tenderbin';

            switch (strtolower($mediaData['storage_type'])) {
                case 'tenderbin':
                    $objectMedia = new MediaObjectTenderBin;
                    $objectMedia->with( $objectMedia::parseWith($mediaData) );
                    break;

                default:
                    throw new \Exception('Object Storage With Name (%s) Is Unknown.');
            }

            return $objectMedia;
        }
    }
}
