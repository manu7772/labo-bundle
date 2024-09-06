<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Component\Jelastic;
use Aequation\LaboBundle\Form\Type\JelasticType;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Service\Interface\FormServiceInterface;
use Aequation\LaboBundle\Service\Interface\LaboBundleServiceInterface;
use Aequation\LaboBundle\Service\Tools\Files;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Twig\Environment;

#[AsAlias(LaboBundleServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: true)]
class LaboBundleService extends AppService implements LaboBundleServiceInterface
{

    public const JELASTIC_FILE = 'jelastic/symfony-jelastic.jps.twig';

    public function __construct(
        RequestStack $requestStack,
        KernelInterface $kernel,
        ParameterBagInterface $parameterBag,
        Security $security,
        AccessDecisionManagerInterface $accessDecisionManager,
        Environment $twig,
        NormalizerInterface $normalizer,
        protected FormServiceInterface $formService,
    ) {
        parent::__construct($requestStack, $kernel, $parameterBag, $security, $accessDecisionManager, $twig, $normalizer);
    }

    public function getJelasticForm(
        Jelastic $data = null,
        array $options = [],
    ): FormInterface
    {
        return $this->formService->getForm(JelasticType::class, $data ?? $this->createJelastic(), $options);
    }

    public function createJelastic(): Jelastic
    {
        $jelastic = new Jelastic();
        $params = $this->getParam('jelastic_data', null);
        if(!empty($params)) {
            $jelastic->setData($params);
        }
        return $jelastic;
    }

    public function getJelasticFile(
        ?array $data = null,
        bool $useTwigTemplating = false,
    ): ?string
    {
        $file = '';
        if(!empty($data)) {
            // Fill with data
            $twig = $this->getTwig();
            if(!$useTwigTemplating && $model = $this->getJelasticModel()) {
                $template = $twig->createTemplate($model);
                $file = $template->render($data);
            }
            if(empty($file)) {
                $twig->render('@AequationLabo/jelastic/symfony-jelastic.jps.twig', $data);
            }
        }
        return $file;
    }

    public function getJelasticModel(): string|false
    {
        $model = Files::getFileContent('templates/'.static::JELASTIC_FILE);
        if(!$model) {
            $model = Files::getFileContent('lib/aequation/labo-bundle/templates/'.static::JELASTIC_FILE);
        }
        return $model;
    }

    public function getMenu(): array
    {
        return [
            'Website' => [
                'route' => 'app_home',
                'access' => 'ROLE_USER',
            ],
            'Admin' => [
                'route' => 'admin_home',
                'access' => 'ROLE_EDITOR',
            ],
            'Labo' => [
                'route' => 'aequation_labo_home',
                'access' => 'ROLE_ADMIN',
            ],
            'Documentation' => [
                'route' => 'aequation_labo_documentation',
                'params' => ['rubrique' => 'home'],
                'access' => 'ROLE_EDITOR',
            ],
        ];
    }

    public function getSubmenu(): array
    {
        $menu = [
            'Labo' => [
                'route' => 'aequation_labo_home',
                'access' => 'ROLE_EDITOR',
            ],
            'CRUDS' => [
                'route' => 'aequation_labo_crudvoters',
                'access' => 'ROLE_SUPER_ADMIN',
            ],
            'Services' => [
                'route' => 'aequation_labo_services',
                'access' => 'ROLE_ADMIN',
            ],
            'Entities' => [
                'route' => 'aequation_labo_entity_list',
                'access' => 'ROLE_ADMIN',
            ],
            'Siteparams' => [
                'route' => 'aequation_labo_siteparams',
                'access' => 'ROLE_ADMIN',
            ],
            'Css & styles' => [
                'route' => 'aequation_labo_css',
                'access' => 'ROLE_ADMIN',
            ],
            'Caches' => [
                'route' => 'aequation_cache_home',
                'access' => 'ROLE_ADMIN',
            ],
            'Php info' => [
                'route' => 'aequation_php_home',
                'access' => 'ROLE_EDITOR',
            ],
            'Jelastic generator' => [
                'route' => 'aequation_jelastic_home',
                'access' => 'ROLE_SUPER_ADMIN',
            ],
            // 'Test modale' => [
            //     'route' => 'aequation_labo_testmodale',
            //     'access' => 'ROLE_EDITOR',
            // ],
        ];
        return $menu;
    }

    public function getEntitymenu(): array
    {
        $menu = [];
        /** @var AppEntityManagerInterface */
        $appEntityService = $this->get(AppEntityManagerInterface::class);
        foreach ($appEntityService->getEntityNames(true, false) as $classname => $shortname) {
            if(!in_array($shortname, ['Crudvoter'])) {
                $menu[$shortname] = [
                    'route' => 'aequation_labo_crudvoter_class',
                    'params' => ['class' => $classname],
                    'access' => 'ROLE_SUPER_ADMIN',
                ];
            }
        }
        return $menu;
    }

}