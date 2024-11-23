<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\AequationLaboBundle;
use Aequation\LaboBundle\Component\Jelastic;
use Aequation\LaboBundle\Form\Type\JelasticType;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\FormServiceInterface;
use Aequation\LaboBundle\Service\Interface\LaboBundleServiceInterface;
use Aequation\LaboBundle\Service\Tools\Classes;
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
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Environment;

use Attribute;
use Exception;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AsAlias(LaboBundleServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: true)]
class LaboBundleService extends AppService implements LaboBundleServiceInterface
{

    public const JELASTIC_FILE = 'jelastic/symfony-jelastic.jps.twig';

    public readonly Files $tool_files;
    protected array $appServices;

    public function __construct(
        RequestStack $requestStack,
        KernelInterface $kernel,
        ParameterBagInterface $parameterBag,
        Security $security,
        AccessDecisionManagerInterface $accessDecisionManager,
        AuthorizationCheckerInterface $authorizationChecker,
        Environment $twig,
        NormalizerInterface $normalizer,
        protected FormServiceInterface $formService,
    ) {
        parent::__construct($requestStack, $kernel, $parameterBag, $security, $accessDecisionManager, $authorizationChecker, $twig, $normalizer);
        $this->tool_files = $this->get('Tool:Files');
        $this->stopPublic();
    }

    protected function stopPublic(): void
    {
        if($this->isDev() && $this->isPublic() && !$this->appContext->isCliXmlHttpRequest()) {
            throw new Exception(vsprintf('WARNING: %s line %d: this service should not be loaded in public firewall (firewall is %s)!%s', [__METHOD__, __LINE__, $this->getFirewall(), PHP_EOL.$this->appContext->getDumped()]));
            // dd('This method '.__METHOD__.' should not be used while in PUBLIC firewall');
        }
    }


    /************************************************************************************************************/
    /** CONTAINER / SERVICES                                                                                    */
    /************************************************************************************************************/

    /**
     * Get all APP services
     * @return array
     */
    public function getAppServices(): array
    {
        return array_filter(
            $this->getServices(),
            fn ($service) => preg_match('/^(App\\\\|Aequation\\\\)/', (string)$service['classname'])
        );
    }

    /**
     * Get all services
     * @return array
     */
    public function getServices(): array
    {
        $this->stopPublic();
        return $this->getCache()->get(
            key: static::APP_CACHENAME_SERVICES_LIST,
            callback: function(ItemInterface $item) {
                if(!empty(static::APP_CACHENAME_SERVICES_LIFE)) {
                    $item->expiresAfter(static::APP_CACHENAME_SERVICES_LIFE);
                }
                /** @var Container $container */
                $container = $this->getContainer();
                $ids = $container->getServiceIds();
                $appServices = [];
                foreach ($ids as $id) {
                    $service = $container->get($id, ContainerInterface::NULL_ON_INVALID_REFERENCE);
                    $appServices[] = [
                        'id' => $id,
                        'classname' => $service ? get_class($service) : null,
                    ];
                }
                return $appServices;
            },
            commentaire: 'All symfony services',
        );
    }


    /************************************************************************************************************/
    /** JELASTIC GENERATION                                                                                     */
    /************************************************************************************************************/

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
        $model = $this->tool_files->getFileContent('templates'.DIRECTORY_SEPARATOR.static::JELASTIC_FILE);
        if(!$model) {
            $model = $this->tool_files->getFileContent(AequationLaboBundle::getProjectPath(true).'templates'.DIRECTORY_SEPARATOR.static::JELASTIC_FILE);
        }
        return $model;
    }


    /************************************************************************************************************/
    /** LABO MENUS                                                                                              */
    /************************************************************************************************************/

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
            'Classes' => [
                'route' => 'aequation_labo_class_list',
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


    /************************************************************************************************************/
    /** CLASSES DESCRIPTION                                                                                     */
    /************************************************************************************************************/

    public function getDeclaredClasses(
        array|object|string $listOfClasses = null
    ): array
    {
        Classes::filterDeclaredClasses(listOfClasses: $listOfClasses, sort: true);
        return $listOfClasses;
    }

    public function getAppAttributesList(
        array|object|string $listOfClasses = null
    ): array
    {
        if(empty($listOfClasses)) $listOfClasses = Classes::REGEX_APP_CLASS;
        // Classes::filterDeclaredClasses($listOfClasses);
        // dd($listOfClasses);
        return Classes::getAttributes(listOfClasses: $listOfClasses);
    }


}