<?php
namespace Poirot\TenderBinClient
{
    use Module\TenderBinClient\Interfaces\iMediaHandler;
    use Poirot\Std\Exceptions\exImmutable;
    use Poirot\Std\Interfaces\Pact\ipFactory;
    use Poirot\Std\Struct\CollectionPriority;
    use Poirot\Std\Struct\DataEntity;
    use Poirot\Std\Type\StdArray;
    use Poirot\Std\Type\StdTravers;
    use Poirot\TenderBinClient\Exceptions\exResourceNotFound;
    use Poirot\TenderBinClient\Model\aMediaObject;
    use Poirot\TenderBinClient\Model\MediaObjectTenderBin;


    /**
     * Magic Touch Media Contents To Infinite Expiration
     *
     * @param \Traversable $content
     *
     * @throws \Exception
     */
    function assertMediaContents($content)
    {
        if (!($content instanceof \Traversable || is_array($content)))
            // Do Nothing!!
            return;


        foreach ($content as $c) {
            if ($c instanceof aMediaObject) {

                $handler = FactoryMediaObject::hasHandlerOfStorage( $c->getStorageType() );

                try {
                    if ($handler)
                    {
                        // Touch Media Bin And Assert Content/Meta
                        //
                        /** @var DataEntity $r */
                        $r = \Poirot\Std\reTry(function () use($handler, $c) {
                            return $handler->client()->touch( $c->getHash() );
                        });
                        $r = $r->get('result');

                        $meta        = $r['bindata']['meta'];
                        $contentType = $r['bindata']['content_type'];

                        $c->setMeta($meta);
                        $c->setContentType($contentType);


                        // Set Versions Available For This Media
                        //
                        $r = $handler->client()->getBinMeta( $c->getHash() );

                        if ( isset($r['versions']) && !empty($r['versions']) ) {
                            $versions = [];
                            foreach ($r['versions'] as $vname => $values) {
                                $uid = $values['bindata']['uid'];
                                $versions[$vname] = $uid;
                            }

                            $r = $handler->client()->listBinMeta( array_values($versions) );

                            // append to media object as version
                            $storageType = $c->getStorageType();
                            foreach ($versions as $vname => $hash)
                            {
                                $subVer = FactoryMediaObject::of(null, $storageType);
                                $subVer->setHash($hash);
                                $subVer->setMeta( $r[$hash] );

                                $c->addVersion(
                                    $vname
                                    , $subVer
                                );

                            }
                        }
                    }

                } catch (exResourceNotFound $e) {
                    // Specific Content Client Exception
                    throw $e;

                } catch (\Exception $e) {
                    // Other Errors Throw To Next Layer!
                    throw $e;
                }

            } elseif (is_array($c) || $c instanceof \Traversable) {
                assertMediaContents($c);
            }
        }
    }

    /**
     * Embed Retrieval Http Link To MediaObjects
     *
     * @param array|\Traversable $content         Content include MediaObject
     * @param callable           $withMediaObject Functor called with media data object
     *                           function(array $contentInclude_link)
     *
     * @return array Prepared content include link(s)
     * @throws \Exception
     */
    function embedLinkToMediaData($content, $withMediaObject = null)
    {
        if ($content instanceof StdArray)
            $content = $content->value;


        if ($content instanceof \Traversable )
            $content = StdTravers::of($content)->toArray();

        if (! is_array($content) )
            throw new \Exception('Medias is not an type of array.');


        $content = StdArray::of($content)->withWalk(function(&$val) use ($withMediaObject) {

            if (! $val instanceof aMediaObject )
                return;

            $val = toResponseMediaObject($val);


            if ($withMediaObject) {
                if ( null === $val = $withMediaObject($val) )
                    throw new \RuntimeException(sprintf(
                        'Functor given for media while Embed Link return NULL.'
                    ));

                if (! is_array($val) )
                    throw new \RuntimeException(sprintf(
                        'Functor given for media while Embed Link return None Array Type.'
                    ));
            }
        });

        // instance access to internal array
        return $content->value;
    }

    /**
     * @see OnThatEmbedMediaLinks
     * @see OnThatEmbedVideoMediaLinks
     */
    function toResponseMediaObject(aMediaObject $val)
    {
        $orig         = $val;
        $val          = [
            'hash'         => $orig->getHash(),
            'storage_type' => $orig->getStorageType(),
            'content_type' => $orig->getContentType(),
        ];

        $meta = $orig->getMeta();
        if (! empty($meta) ) {
            $val['meta'] = StdTravers::of($meta)->toArray(function ($val) {
                return empty($val);
            });

            if (empty($val['meta']))
                $val['meta'] = null;
        }

        $versions = $orig->getVersions();
        if (! empty($versions) ) {
            $val['versions'] = StdArray::of($versions)->withWalk(function (&$val){
                $val = toResponseMediaObject($val);
            });
        }

        $val['_link'] = $orig->get_Link();

        return $val;
    }


    class FactoryMediaObject
        implements ipFactory
    {
        /** @var CollectionPriority  */
        protected static $handlers;
        /** @var iMediaHandler */
        protected static $defaultHandler;



        /**
         * Factory With Valuable Parameter
         *
         * @param null   $mediaData
         * @param string $storageType
         *
         * @return MediaObjectTenderBin
         * @throws \Exception
         */
        static function of($mediaData = null, $storageType = null)
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
                $mediaData['storage_type'] = ($storageType)
                    ? $storageType
                    : null;



            if (! isset($mediaData['storage_type']) )
                $handler = self::getDefaultHandler();
            else
                $handler = static::hasHandlerOfStorage($mediaData['storage_type']);


            ## Registered Handler
            #
            if ( $handler ) {
                $mediaData['storage_type'] = $handler->getType();
                return $handler->newMediaObject($mediaData);
            }


            $storageType = static::_normalizeHandlerName($mediaData['storage_type']);

            switch ($storageType) {
                case 'tenderbin':
                    $objectMedia = new MediaObjectTenderBin;
                    $objectMedia->with( $objectMedia::parseWith($mediaData) );
                    break;

                default:
                    throw new \Exception(sprintf(
                        'Object Storage With Name (%s) Is Unknown.'
                        , $storageType
                    ));
            }


            return $objectMedia;
        }


        /**
         * Add new Handler Media Object
         *
         * @param iMediaHandler $handler
         * @param int           $weight
         */
        static function addHandler(iMediaHandler $handler, $weight = 10)
        {
            self::_getHandlersQueue()->insert($handler, $weight);
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
            foreach (clone self::_getHandlersQueue() as $handler) {
                if ( $handler->canHandleMedia($storageType) )
                    return $handler;
            }

            return false;
        }

        /**
         * Set Default Handler
         *
         * @param iMediaHandler $handler
         */
        static function setDefaultHandler(iMediaHandler $handler)
        {
            if (isset(self::$defaultHandler))
                throw new exImmutable(sprintf(
                    'Handler (%s) is take in place before.'
                    , get_class(self::$defaultHandler)
                ));


            self::$defaultHandler = $handler;
        }

        /**
         * Get Default Handler
         *
         * @return iMediaHandler
         */
        static function getDefaultHandler()
        {
            if ( isset(self::$defaultHandler) )
                return self::$defaultHandler;


            foreach (clone self::_getHandlersQueue() as $handler)
                return $handler;
        }


        // ..

        protected static function _normalizeHandlerName($handlerName)
        {
            return strtolower($handlerName);
        }

        private static function _getHandlersQueue()
        {
            if (!isset(self::$handlers))
                self::$handlers = new CollectionPriority;

            return self::$handlers;
        }
    }
}
