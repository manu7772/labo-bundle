<?php
namespace Aequation\LaboBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AequationLaboBundle extends Bundle
{

    public static function getProjectPath(
        bool $directory_separator = true
    ): string
    {
        return \dirname(__DIR__, 1).($directory_separator ? DIRECTORY_SEPARATOR : '');
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AequationLaboCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1);
        parent::build($container);
    }

}