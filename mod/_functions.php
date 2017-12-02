<?php
namespace Poirot\TenderBinClient
{

    use Module\TenderBinClient\Interfaces\iMediaHandler;
    use Poirot\Std\Interfaces\Pact\ipFactory;
    use Poirot\Std\Type\StdArray;
    use Poirot\Std\Type\StdTravers;
    use Poirot\TenderBinClient\Exceptions\exResourceNotFound;
    use Poirot\TenderBinClient\Model\aMediaObject;
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
     * @param array|\Traversable $content   Content include MediaObject
     * @param callable           $withMedia Functor called with media data object
     *                           function(array $contentInclude_link)
     *
     * @return array Prepared content include link(s)
     * @throws \Exception
     */
    function embedLinkToMediaData($content, $withMedia = null)
    {
        if ($content instanceof \Traversable )
            $content = StdTravers::of($content)->toArray();

        if (! is_array($content) )
            throw new \Exception('Medias is not an type of array.');


        $content = StdArray::of($content)->withWalk(function(&$val) {
            if (! $val instanceof aMediaObject )
                return;

            $orig         = $val;
            $val          = StdTravers::of($val)->toArray();
            $val['_link'] = $orig->getLink();
        });


        // instance access to internal array
        return ($withMedia) ? $withMedia($content->value) : $content->value;
    }


    class FactoryMediaObject
        implements ipFactory
    {
        protected static $handlers = [];


        /**
         * Factory With Valuable Parameter
         *
         * @param null   $mediaData
         * @param string $storageType
         *
         * @return MediaObjectTenderBin
         * @throws \Exception
         */
        static function of($mediaData = null, $storageType = 'tenderbin')
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
                $mediaData['storage_type'] = ($storageType) ? $storageType : 'tenderbin';


            $storageType = static::_normalizeHandlerName($mediaData['storage_type']);

            ## Registered Handler
            #
            if ( $handler = static::hasHandlerOfStorage($storageType) )
                return $handler->newMediaObject($mediaData);


            switch ($storageType) {
                case 'tenderbin':
                    $objectMedia = new MediaObjectTenderBin;
                    $objectMedia->with( $objectMedia::parseWith($mediaData) );
                    break;

                default:
                    throw new \Exception('Object Storage With Name (%s) Is Unknown.');
            }


            return $objectMedia;
        }


        /**
         * Add new Handler Media Object
         *
         * @param iMediaHandler $handler
         */
        static function addHandler(iMediaHandler $handler)
        {
            static::$handlers[] = $handler;
        }

        /**
         * Handler
         *
         * @param string $storageType
         *
         * @return iMediaHandler|false
         */
        static function hasHandlerOfStorage($storageType)
        {
            $storageType = static::_normalizeHandlerName($storageType);

            /** @var iMediaHandler $handler */
            foreach (static::$handlers as $handler) {
                if ( $handler->canHandleMedia($storageType) )
                    return $handler;
            }

            return false;
        }


        // ..

        protected static function _normalizeHandlerName($handlerName)
        {
            return strtolower($handlerName);
        }
    }
}
