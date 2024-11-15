<?php
namespace Aequation\LaboBundle\DependencyInjection;

use Aequation\LaboBundle\AequationLaboBundle;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class AequationLaboExtension extends Extension
{

    public const ADD_CONFIG = false;

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );
        $loader->load('services.yaml');

        if(static::ADD_CONFIG) {
            $configuration = $this->getConfiguration($configs, $container);
            $config = $this->processConfiguration($configuration, $configs);
            // dd($config, $container->getParameter('kernel.environment'));
        }

        $this->configureAssetMapper($container);

    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($config, $container);
    }


    // ASSET MAPPER

    protected function configureAssetMapper(ContainerBuilder $container): void
    {
        if($this->isAssetMapperAvailable($container)) {
            $container->prependExtensionConfig('framework', [
                'asset_mapper' => [
                    'paths' => [
                        AequationLaboBundle::getProjectPath('/assets/js') => '@aequation/ux-labo-utilities',
                    ],
                ],
            ]);
        }
    }



    /**
     * Utilities
     */

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if(!interface_exists(AssetMapperInterface::class)) {
            return false;
        }
        // check that FrameworkBundle 6.3 or higher is installed
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if(!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }
        return is_file($bundlesMetadata['FrameworkBundle']['path'].'/Resources/config/asset_mapper.php');
    }

}