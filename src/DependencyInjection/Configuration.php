<?php
namespace Aequation\LaboBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    private bool $isDev = false;

    public function __construct(
        private array $config,
        private ContainerBuilder $container,
    )
    {
        $this->isDev = strtolower($container->getParameter('kernel.environment')) === 'dev';
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('aequation_labo');

        $treeBuilder->getRootNode()
            ->children()
                // super admin
                ->scalarNode('main_sadmin')
                    ->info('Email of the main super admin of this webapp.')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->defaultValue('manu7772@gmail.com')
                ->end()
                // Basics data (for fixtures)
                ->scalarNode('basics_dir')
                    ->info('Basics data (for fixtures or app:basics Command).')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                // cleanable directorys while clear all
                ->arrayNode('clean_directorys')
                    ->info('List of directories to clear when clearing the webapp (eg. just before all Fixtures lauch).')
                    ->scalarPrototype()->end()
                ->end()
                // CSS colors classes
                // ->arrayNode('custom_colors')
                //     ->info('List of all CSS colors classes.')
                //     ->scalarPrototype()
                //     // ->cannotBeEmpty()
                //     ->end()
                // ->end()

            ->end()
        ;

        return $treeBuilder;
    }
}