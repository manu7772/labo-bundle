<?php
namespace Aequation\LaboBundle;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Throwable;

class AequationLaboCompilerPass implements CompilerPassInterface
{
    public const DATABASE_PARAMETERS = true;
    public const EXECUTE_DISPATCH = true;
    public const REMOVE_DISPATCHEDS = true;

    public function Process(
        ContainerBuilder $container
    ): void
    {
        // if($container->hasParameter('main_sadmin')) {
        //     // $resources = $container->getParameter('main_sadmin') ?: [] ;
        //     // array_unshift($resources, '@AequationLabo/form/labo_app_layout.html.twig');
        //     $container->setParameter('main_sadmin', 'test@test.com');
        // }

        if(static::DATABASE_PARAMETERS) {
            $siteparams = $this->getParams();
            foreach ($siteparams as $param) {
                $container->setParameter($param['name'], $param['paramvalue']);
            }
        }
    }

    private function getParams(): array
    {
        /** @var Connection $connexion */
        $connexion = DriverManager::getConnection(['url' => $_ENV['DATABASE_URL']]);
        try {
            $database_params = $connexion->executeQuery('SELECT P.name, P.paramvalue, P.dispatch FROM siteparams as P')->fetchAllAssociative();
        } catch (Throwable $th) {
            $database_params = false;
        }
        if(empty($database_params)) return [];
        $params = array_map(
            function($param) {
                $param['paramvalue'] = json_decode($param['paramvalue'], true);
                return $param;
            },
            $database_params
        );
        if(static::EXECUTE_DISPATCH) {
            foreach ($params as $idx => $param) {
                $remove = false;
                if($param['dispatch'] && is_array($param['paramvalue']) && !array_is_list($param['paramvalue'])) {
                    foreach ($param['paramvalue'] as $key => $val) {
                        if(preg_match('/^[\w\d_-]+$/', $key)) {
                            $newid = $param['name'].'.'.$key;
                            $params[] = [
                                'name' => $newid,
                                'paramvalue' => $val,
                            ];
                            $remove = true;
                        }
                    }
                }
                if($remove && static::REMOVE_DISPATCHEDS) unset($params[$idx]);
            }
        }
        return $params;
    }

}