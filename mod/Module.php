<?php
namespace Module\TenderBinClient
{
    use Poirot\Std\Interfaces\Struct\iDataEntity;
    use Poirot\Application\Interfaces\Sapi;
    use Poirot\Ioc\Container;
    use Poirot\Ioc\Container\BuildContainer;
    use Poirot\Loader\Autoloader\LoaderAutoloadAggregate;
    use Poirot\Loader\Autoloader\LoaderAutoloadNamespace;
    use Poirot\Loader\Interfaces\iLoaderAutoload;


    class Module implements Sapi\iSapiModule
        , Sapi\Module\Feature\iFeatureModuleAutoload
        , Sapi\Module\Feature\iFeatureModuleMergeConfig
        , Sapi\Module\Feature\iFeatureModuleNestServices
    {
        /**
         * Register class autoload on Autoload
         *
         * priority: 1000 B
         *
         * @param LoaderAutoloadAggregate $baseAutoloader
         *
         * @return iLoaderAutoload|array|\Traversable|void
         */
        function initAutoload(LoaderAutoloadAggregate $baseAutoloader)
        {
            #$nameSpaceLoader = \Poirot\Loader\Autoloader\LoaderAutoloadNamespace::class;
            $nameSpaceLoader = 'Poirot\Loader\Autoloader\LoaderAutoloadNamespace';
            /** @var LoaderAutoloadNamespace $nameSpaceLoader */
            $nameSpaceLoader = $baseAutoloader->loader($nameSpaceLoader);
            $nameSpaceLoader->addResource(__NAMESPACE__, __DIR__);
        }

        /**
         * @inheritdoc
         */
        function initConfig(iDataEntity $config)
        {
            return \Poirot\Config\load(__DIR__ . '/../config/mod-tenderbin_client');
        }

        /**
         * Get Nested Module Services
         *
         * it can be used to manipulate other registered services by modules
         * with passed Container instance as argument.
         *
         * priority not that serious
         *
         * @param Container $moduleContainer
         *
         * @return null|array|BuildContainer|\Traversable
         */
        function getServices(Container $moduleContainer = null)
        {
            $conf = \Poirot\Config\load(__DIR__ . '/../config/mod-tenderbin_client.services', true);
            return $conf;
        }
    }
}

namespace Module\TenderBinClient
{
    use Poirot\TenderBinClient\Client;

    /**
     * @method static Client ClientTender()
     */
    class Services extends \IOC
    { }
}
