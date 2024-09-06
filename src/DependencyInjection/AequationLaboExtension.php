<?php
namespace Aequation\LaboBundle\DependencyInjection;

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

    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($config, $container);
    }

}