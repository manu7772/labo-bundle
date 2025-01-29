<?php
namespace Aequation\LaboBundle\Service\TwigExtensions;

use Aequation\LaboBundle\Model\Final\FinalUserInterface;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Model\Interface\EnabledInterface;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Model\Interface\PreferedInterface;
use Aequation\LaboBundle\Service\AppService;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Tools\Classes;
use Aequation\LaboBundle\Service\Tools\Encoders;
use Aequation\LaboBundle\Service\Tools\Strings;
// Symfony
use EasyCorp\Bundle\EasyAdminBundle\Collection\EntityCollection;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use Exception;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Twig\Markup;
use Twig\TwigFilter;

/**
 * Defines the filters and functions used to render the bundle's templates.
 * Also injects the admin context into Twig global variables as `ea` in order
 * to be used by admin templates.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class LaboTwigExtensions extends AbstractExtension
{

    public readonly AppEntityManagerInterface $appEntityManager;

    public function __construct(
        private AppService $appService,
        private TranslatorInterface $translator,
    ) {
        $this->appEntityManager = $this->appService->get(AppEntityManagerInterface::class);
    }

    /**
     * Get Twig functions
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            // Get route for entity
            new TwigFunction('entityAction', [$this, 'getRoute']),
            // Get entity template(s)
            new TwigFunction('entityTemplate', [$this, 'getTemplates']),
            new TwigFunction('printField', [$this, 'printField']),
            new TwigFunction('cellPrint', [$this, 'cellPrint']),
            new TwigFunction('laboPrepareDto', [$this, 'laboPrepareDto']),
            new TwigFunction('laboFilterDtoClass', [$this, 'laboFilterDtoClass']),
        ];
    }

    /**
     * Get Twig filters
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('instance', [$this, 'isInstance']),
        ];
    }


    /*************************************************************************************
     * FUNCTIONS
     *************************************************************************************/

    /**
     * Get route & params (array) for entity
     * if null = route not found
     * if false = route exists but not granted
     * @param AppEntityInterface $entity
     * @param array $params
     * @param boolean $controls
     * @return array|null|false
     */
    public function getRoute(
        AppEntityInterface|string $entity,
        string $action,
        array $params = [],
        bool $controls = true,
    ): array|null|false
    {
        $shortname = $entity instanceof AppEntityInterface ? $entity->getShortname(true) : strtolower(Classes::getShortname($entity) ?? $entity);
        $route = null;
        switch ($action) {
            case 'list':
            case 'index':
                $route = [
                    '_route' => 'aequation_labo_entity_'.$shortname.'_index',
                    '_route_params' => $params,
                ];
                break;
            case 'show':
                $route = [
                    '_route' => 'aequation_labo_entity_'.$shortname.'_show',
                    '_route_params' => array_merge($params, ['id' => $entity->getId()]),
                ];
                break;
            case 'new':
                $route = [
                    '_route' => 'aequation_labo_entity_'.$shortname.'_new',
                    '_route_params' => $params,
                ];
                break;
            case 'edit':
                $route = [
                    '_route' => 'aequation_labo_entity_'.$shortname.'_edit',
                    '_route_params' => array_merge($params, ['id' => $entity->getId()]),
                ];
                break;
            case 'delete':
                $route = [
                    '_route' => 'aequation_labo_entity_'.$shortname.'_delete',
                    '_route_params' => array_merge($params, ['id' => $entity->getId()]),
                ];
                break;
            default:
                // action unknown
                break;
        }
        if(empty($route)) return null; // route not found
        if($controls) {
            // check if granted
            if(!$this->appService->routeExists($route['_route'])) return null;
            if(!$this->appService->isGranted('ROLE_ADMIN')) return false;
        }
        return $route;
    }

    /**
     * Get template(s) for entity action
     *
     * @param string|AppEntityInterface $entity
     * @param string $action
     * @param boolean $allowMultiple
     * @return string|array|null
     */
    public function getTemplates(
        string|AppEntityInterface $entity,
        string $action,
    ): string|array|null
    {
        $shortname = Classes::getShortname($entity);
        if(empty($shortname)) throw new Exception(vsprintf('Erreur %s ligne %d: entity %s non reconnue', [__METHOD__, __LINE__, $entity]));
        return [
            vsprintf('@AequationLabo/cruds/%s/%s.html.twig', [strtolower($shortname), $action]),
            vsprintf('@AequationLabo/cruds/%s/%s.html.twig', ['default', $action]),
        ];
    }

    public function printField(
        AppEntityInterface $entity,
        string $name,
    ): Markup
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->enableMagicCall()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
        return Encoders::fieldRepresentation($propertyAccessor->getValue($entity, $name), true);
    }

    public function cellPrint(
        string|AppEntityInterface $entity,
        string $name,
        string $context = 'index',
    ): array
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->enableMagicCall()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
        $value = $propertyAccessor->getValue($entity, $name);
        $info = [
            'type' => gettype($value),
            'value' => Encoders::fieldRepresentation($value, true),
        ];
        return $info;
    }

    public function laboPrepareDto(
        EntityCollection $entities,
        string $action,
    ): void
    {
        $user = $this->appService->getUser();
        switch ($action) {
            case 'index':
                foreach ($entities as $entityDto) {
                    $entity = $entityDto->getInstance();
                    // Fields
                    foreach ($entityDto->getFields() as $field) {
                        // field classes
                        $classes = preg_split('/\s+/', $field->getCssClass());
                        if($entity instanceof EnabledInterface && $entity->isSoftdeleted()) {
                            // Inactive entity
                            $classes[] = 'bg-warning-subtle';
                            $classes[] = 'text-warning';
                            $classes[] = 'text-decoration-line-through';
                            $classes[] = 'link-underline-warning';
                        } else if($entity instanceof EnabledInterface && (!$entity->isActive() || ($entity instanceof LaboUserInterface && !$entity->isVerified()))) {
                            // Inactive entity
                            $classes = array_filter($classes, fn($class) => !preg_match('/^text-/', $class));
                            $classes[] = 'text-tertiary';
                            if(!$entity->isActive()) {
                                $classes[] = 'text-decoration-line-through';
                                $classes[] = 'link-underline-hover';
                            }
                        } else if($entity instanceof PreferedInterface && $entity->isPrefered()) {
                            // is PREFERED
                            $classes[] = 'bg-success-subtle';
                            $classes[] = 'text-success';
                        } else if($entity instanceof LaboUserInterface && $entity === $user) {
                            // is ME !
                            $classes[] = 'bg-info-subtle';
                        } else if($entity instanceof FinalUserInterface && $entity->isAdmin()) {
                            // is ADMIN
                            $classes = array_filter($classes, fn($class) => !preg_match('/^text-/', $class));
                            $classes[] = 'text-primary-emphasis';
                            $classes[] = 'text-decoration-underline';
                        }
                        if($entity instanceof FinalUserInterface && $this->appService->isUserGranted($entity, 'ROLE_SUPER_ADMIN')) {
                            // is SUPERADMIN
                            $classes = array_filter($classes, fn($class) => !preg_match('/^(text|bg)-/', $class));
                            $classes[] = 'bg-danger-subtle';
                            $classes[] = 'text-danger';
                        }
                        $classes = array_unique($classes);
                        $field->setCssClass(implode(' ', $classes));
                    }
                }
                break;
        }
    }

    public function laboFilterDtoClass(
        string $class,
        string $pattern = null,
    ): string
    {
        $classes = preg_split('/\s+/', $class);
        if(empty($pattern)) $pattern = '/^(text|link)-(decoration|underline)/';
        $classes = array_filter($classes, fn($class) => !preg_match($pattern, $class));
        return implode(' ', $classes);
    }

    public function isInstance(
        mixed $value,
        string|object $class,
    ): bool
    {
        if(is_object($class)) $class = $class instanceof AppEntityInterface ? $class->getClassname() : get_class($class);
        return is_object($value) && $value instanceof $class;
    }

}
