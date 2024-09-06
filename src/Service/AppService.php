<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Component\AppContext;
use Aequation\LaboBundle\Component\AppContextTemp;
use Aequation\LaboBundle\Component\Identity;
use Aequation\LaboBundle\Component\Interface\AppContextInterface;
use Aequation\LaboBundle\Model\Attribute\ClassCustomService;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Model\Interface\MenuInterface;
use Aequation\LaboBundle\Model\Interface\SlugInterface;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Model\Interface\WebpageInterface;
use Aequation\LaboBundle\Service\Base\BaseService;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Service\Interface\CacheServiceInterface;
use Aequation\LaboBundle\Service\Interface\ServiceInterface;
use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use Aequation\LaboBundle\Service\Tools\Classes;
use Aequation\LaboBundle\Service\Tools\Files;
use Aequation\LaboBundle\Service\Tools\HttpRequest;
use Aequation\LaboBundle\Service\Tools\Strings;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\UX\Turbo\TurboBundle;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Doctrine\Common\Collections\Collection;
use Twig\Environment;
use Twig\Loader\LoaderInterface;
use Twig\Markup;

use UnitEnum;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use ArrayObject;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

use function Symfony\Component\String\u;

#[AsAlias(AppServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: false)]
class AppService extends BaseService implements AppServiceInterface
{

    public readonly ContainerInterface $container;
    protected ?FirewallConfig $firewallConfig;
    protected AppContextInterface $appContext;
    protected ?Request $request;
    protected ?Session $session;
    protected Identity $identity;
    protected string $project_dir;
    protected ?string $_route;
    protected mixed $_route_params;
    protected array $appServices;

    public function __construct(
        public readonly RequestStack $requestStack,
        public readonly KernelInterface $kernel,
        public readonly ParameterBagInterface $parameterBag,
        public readonly Security $security,
        public readonly AccessDecisionManagerInterface $accessDecisionManager,
        public readonly Environment $twig,
        public readonly NormalizerInterface $normalizer,
    ) {
        $this->container = $this->kernel->getContainer();
        $this->project_dir = $this->kernel->getProjectDir();
        $this->initializeAppContext();
    }


    /************************************************************************************************************/
    /** CONTAINER / SERVICES                                                                                    */
    /************************************************************************************************************/

    /**
     * Has service (only if public)
     * @param string $id
     * @return bool
     */
    public function has(
        string $id
    ): bool
    {
        return $this->container->has($id);
        // return $this->container?->has($id) ?: false;
    }

    /**
     * Get service (only if public)
     * @param string $id
     * @param [type] $invalidBehavior
     * @return object|null
     */
    public function get(
        string $id,
        int $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE
    ): ?object
    {
        if(!isset($this->container)) return null;
        return $this->container->get($id, $invalidBehavior);
    }

    /**
     * Get all APP services
     * @param callable|null $filter
     * @return array
     */
    public function getAppServices(
        callable $filter = null,
    ): array
    {
        return $this->getAppClasses(true, $filter);
    }

    /**
     * Get all APP classes
     * @param boolean $withObjectOnly
     * @param callable|null $filter
     * @return array
     */
    public function getAppClasses(
        bool $withObjectOnly = false,
        callable $filter = null,
    ): array
    {
        if($this->isDev() && $this->isPublic()) {
            dd('This method '.__METHOD__.' should not be used in PUBLIC firewall');
        }
        if(!isset($this->appServices)) {
            $this->appServices = $this->getCache()->get(
                key: static::APP_CACHENAME_SERVICES_LIST,
                callback: function(ItemInterface $item) {
                    if(!empty(static::APP_CACHENAME_SERVICES_LIFE)) {
                        $item->expiresAfter(static::APP_CACHENAME_SERVICES_LIFE);
                    }
                    return Files::getInFilesPhpInfo(static::SOURCES_PHP, true);
                },
                commentaire: 'All PHP classes/interfaces/traits',
            );
            // complete informations...
            foreach ($this->appServices as $key => $service) {
                try {
                    $this->appServices[$key]['service'] = $this->get($service['classname']);
                } catch (\Throwable $th) {
                    $this->appServices[$key]['service'] = false;
                }
            }
        }
        $appServices = $withObjectOnly
            ? array_filter($this->appServices, fn($service) => is_object($service['service']))
            : $this->appServices;
        return is_callable($filter)
            ? $filter($appServices)
            : $appServices;
    }


    /************************************************************************************************************/
    /** TWIG                                                                                                    */
    /************************************************************************************************************/

    public function getTwig(): Environment
    {
        return $this->twig;
    }

    public function getTwigLoader(): LoaderInterface
    {
        return $this->twig->getLoader();
    }


    /************************************************************************************************************/
    /** APP CONTEXT                                                                                             */
    /************************************************************************************************************/

    /**
     * TRY Initialize AppContext
     * @param ?SessionInterface $session = null
     * @return boolean
     */
    public function initializeAppContext(
        ?SessionInterface $session = null
    ): bool
    {
        if(!$this->hasAppContext(false)) {
            // Try find session
            $session ??= $this->getSession();
            $this->appContext = $session instanceof SessionInterface
                ? new AppContext($this, $session->get(AppService::CONTEXT_SESSNAME, null))
                : new AppContextTemp($this, []);
        }
        return $this->appContext->isValid();
    }

    public function getAppContext(): ?AppContextInterface
    {
        $this->initializeAppContext();
        return $this->appContext;
    }

    public function hasAppContext(
        bool $try_initialize = true
    ): bool
    {
        if($try_initialize) $this->initializeAppContext();
        return isset($this->appContext) && !($this->appContext instanceof AppContextTemp);
    }

    public function __isset($name)
    {
        return isset($this->appContext) && property_exists($this->appContext, $name);
    }

    public function __get($name)
    {
        return $this->appContext->$name;
    }

    public function __call($name, $arguments)
    {
        return $this->appContext->$name(...$arguments);
    }



    /****************************************************************************************************/
    /** NORMALIZER / SERIALIZER                                                                         */
    /****************************************************************************************************/

    public function getNormalized(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null
    {
        if($object instanceof Collection) $object = $object->toArray();
        if(is_array($object)) $object = array_values($object); // for React, can not be object, but array
        return $this->normalizer->normalize($object, $format, $context);
    }


    /************************************************************************************************************/
    /** DIRS                                                                                                    */
    /************************************************************************************************************/

    public function getProjectDir(
        bool $endSeparator = false,
    ): string
    {
        return $this->getDir(null, $endSeparator);
    }

    public function getDir(
        ?string $path = null,
        bool $endSeparator = false,
    ): string|false
    {
        // $this->project_dir ??= $this->kernel->getProjectDir();
        $dir = preg_replace('/\\'.DIRECTORY_SEPARATOR.'+/', DIRECTORY_SEPARATOR, $this->project_dir.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR);
        if(!$endSeparator) {
            $dir = preg_replace('/\\'.DIRECTORY_SEPARATOR.'*$/', '', $dir);
        }
        return $dir;
        return file_exists($dir) ? $dir : false;
    }


    /************************************************************************************************************/
    /** SECURITY                                                                                                */
    /************************************************************************************************************/

    public function isDev(): bool
    {
        if(isset($this->appContext)) return $this->appContext->isDev();
        return $this->kernel->getEnvironment() === 'dev';
    }

    public function isProd(): bool
    {
        if(isset($this->appContext)) return $this->appContext->isProd();
        return $this->kernel->getEnvironment() === 'prod';
    }

    public function isTest(): bool
    {
        if(isset($this->appContext)) return $this->appContext->isTest();
        return $this->kernel->getEnvironment() === 'test';
    }

    public function getEnvironment(): string
    {
        if(isset($this->appContext)) return $this->appContext->getEnvironment();
        return $this->kernel->getEnvironment();
    }

    /**
     * Get current User
     * @return LaboUserInterface|null
     */
    public function getUser(): ?LaboUserInterface
    {
        if(isset($this->appContext)) return $this->appContext->getUser();
        return $this->security->getUser();
    }

    public function updateContextUser(
        LoginSuccessEvent $event
    ): static
    {
        // check if User is current
        if($this->isDev() && $event->getUser() !== $this->security->getUser()) {
            throw new Exception(vsprintf('Error %s line %d: while trying to update context user, event user %s and security user %s are not same!', [__METHOD__, __LINE__, $event->getUser(), $this->security->getUser()]));
        }
        $this->appContext->update();
        return $this;
    }

    public function getMainSAdmin(): ?LaboUserInterface
    {
        return $this->get(LaboUserService::class)->getMainSAdmin();
    }

    public function getMainAdmin(): ?LaboUserInterface
    {
        return $this->get(LaboUserService::class)->getMainAdmin();
    }

    /**
     * Subjec is granted with attributes for current User
     * @param mixed $attributes
     * @param ?mixed $subject
     * @return boolean
     */
    public function isGranted(
        mixed $attributes,
        mixed $subject = null,
    ): bool
    {
        if($attributes instanceof LaboUserInterface) {
            $attributes = $attributes->getHigherRole();
        }
        return $this->security->isGranted($attributes, $subject);
    }

    public function isUserGranted(
        mixed $attributes,
        mixed $subject = null,
        LaboUserInterface $user = null,
        string $firewallName = null,
    ): bool
    {
        $user ??= $this->getUser();
        $firewallName ??= $this->appContext->getFirewallName();
        if($user instanceof LaboUserInterface) {
            $token = new UsernamePasswordToken($user, $firewallName, $user?->getRoles() ?: []);
            return $this->accessDecisionManager->decide($token, (array)$attributes, $subject);
        }
        if(!in_array($firewallName, static::PUBLIC_FIREWALLS)) return false;
        return $this->isGranted($attributes, $subject);
    }

    /**
     * Is valid entity for an action (show, edit, remove, send, etc.)
     * @param AppEntityInterface $entity
     * @param string $action
     * @param User|null $user
     * @return boolean
     */
    public function isValidForAction(
        AppEntityInterface $entity,
        string $action,
        ?LaboUserInterface $user = null,
        string $firewallName = null,
    ): bool
    {
        // return false;
        // $user = $this->getUser();
        // $entityService = $this->getEntityService($entity);
        // return $entityService->isValidForAction($entity, $action, $user);
        return $this->isUserGranted($action, $entity, $user, $firewallName);
    }

    // /**
    //  * Checks if the attribute is granted against the current authentication token and optionally supplied subject.
    //  * @throws \LogicException
    //  */
    // public function isVoterGranted(mixed $attribute, mixed $subject = null): bool
    // {
    //     if (!$this->container->has('security.authorization_checker')) {
    //         throw new \LogicException('The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".');
    //     }
    //     return $this->container->get('security.authorization_checker')?->isGranted($attribute, $subject) ?? false;
    // }


    /************************************************************************************************************/
    /** SERVICES                                                                                                */
    /************************************************************************************************************/

    public static function getClassServiceName(
        string|AppEntityInterface $objectOrClass
    ): ?string
    {
        $attrs = Classes::getClassAttributes($objectOrClass, ClassCustomService::class, true);
        if(count($attrs)) {
            $attr = reset($attrs);
            return $attr->service;
        }
        return null;
    }

    public function getClassService(
        string|AppEntityInterface $objectOrClass
    ): ?ServiceInterface
    {
        $serviceName = $this->getClassServiceName($objectOrClass);
        return $this->has($serviceName)
            ? $this->get($serviceName)
            : null;
    }


    /************************************************************************************************************/
    /** DATETIME / STRING / DARKMODE                                                                            */
    /************************************************************************************************************/

    public function getCurrentTimezone(
        bool $asString = false
    ): string|DateTimeZone
    {
        $tz = $this->appContext->getTimezone();
        return $asString ? $tz->getName() : $tz;
    }

    public function getDatetimeTimezone(
        string $date = null
    ): DateTimeImmutable
    {
        $tz = $this->getCurrentTimezone(false);
        if(empty($date)) {
            $date = $this->appContext?->getDater() ?: AppContext::DEFAULT_DATER;
        }
        return new DateTimeImmutable($date, $tz);
    }

    public function getCurrentDatetime(): DateTimeImmutable
    {
        return $this->appContext?->getDatenow() ?: $this->getDatetimeTimezone();
    }

    public function getStringEncoder(): string
    {
        return Strings::CHARSET;
    }

    public function getDarkmode(): bool
    {
        return $this->appContext->getDarkmode();
    }


    /************************************************************************************************************/
    /** REQUEST/SESSION                                                                                         */
    /************************************************************************************************************/

    public function getRequest(): ?Request
    {
        return $this->getCurrentRequest();
    }

    public function getCurrentRequest(): ?Request
    {
        return $this->request ??= $this->requestStack?->getCurrentRequest() ?: null;
    }

    public function getSession(): ?SessionInterface
    {
        return static::getRequestSession($this->getCurrentRequest());
    }

    public static function getRequestSession(
        ?Request $request
    ): ?SessionInterface
    {
        return $request && $request->hasSession() ? $request->getSession() : null;
    }

    public function getRequestAttribute(string $name, mixed $default = null): mixed
    {
        return $this->getCurrentRequest()?->attributes?->get($name, $default) ?? $default;
    }

    public function getRequestContext(): ?RequestContext
    {
        /** @var RouterInterface $router */
        $router = $this->get('router');
        return $router instanceof RouterInterface
            ? $router->getContext()
            : null;
    }

    public function setSessionData(
        string $name,
        mixed $data,
    ): static
    {
        $this->getSession()->set($name, $data);
        return $this;
    }

    public function getSessionData(
        string $name,
        mixed $default,
    ): mixed
    {
        $session = $this->getSession();
        return $session
            ? $session->get($name, $default)
            : $default;
    }


    /************************************************************************************************************/
    /** FIREWALLS                                                                                               */
    /************************************************************************************************************/

    // public function getFirewalls(): array
    // {
    //     return $this->getParameter('security.firewalls');
    // }

    // public function getMainFirewalls(): array
    // {
    //     return array_filter($this->getParameter('security.firewalls'), fn($fw) => !in_array($fw, static::EXCLUDED_FIREWALLS));
    // }

    // public function getFirewallChoices(
    //     bool $onlyMains = true,
    // ): array
    // {
    //     return $this->appContext->getFirewallChoices($onlyMains);
    //     $firewalls = $onlyMains
    //         ? $this->getMainFirewalls()
    //         : $this->getFirewalls();
    //     return array_combine($firewalls, $firewalls);
    // }

    // public function getFirewallName(): ?string
    // {
    //     return $this->appContext->getFirewallName();
    //     // $this->firewallConfig ??= empty($request = $this->getRequest()) ? null : $this->security->getFirewallConfig($request);
    //     // return $this->firewallConfig instanceof FirewallConfig
    //     //     ? $this->firewallConfig->getName()
    //     //     : null;
    //     // OLD METHOD / 
    //     // $firewall = $this->getRequestAttribute('_firewall_context');
    //     // if($this->isDev() && empty($firewall)) {
    //     //     // DEV ALERT
    //     //     dd($this->getFirewalls(), vsprintf('Error %s line %d: could not determine firewall name : got %s!', [__METHOD__, __LINE__, json_encode($firewall)]), $firewall, $this->security, $this->getRequest(), $this->getCurrentRequest()?->attributes?->all() ?? '<no attributes>');
    //     // }
    //     // return $fullname
    //     //     ? $firewall
    //     //     : u($firewall)->afterLast('.');
    // }

    // public function isPublic(): bool
    // {
    //     return $this->appContext->isPublic();
    //     // $fw = strtolower($this->getFirewallName());
    //     // return in_array($fw, static::PUBLIC_FIREWALLS);
    // }

    // public function isPrivate(): bool
    // {
    //     return $this->appContext->isPrivate();
    //     // return !$this->isPublic();
    // }

    // public function isSecured(): bool
    // {
    //     return !$this->isPublic();
    // }


    /************************************************************************************************************/
    /** TURBO                                                                                                   */
    /************************************************************************************************************/

    public function getDataTurboBodyAttrs(): string
    {
        $test = false;
        return $test ? ' data-turbo=false' : '';
    }

    public function getTurboMetas(
        bool $asMarkup = true,
    ): string|Markup
    {
        $metas = [];
        // Turbo refresh
        $turbo_refresh = $this->getParam('turbo-refresh-scroll', null);
        if(!empty($turbo_refresh)) {
            $metas[] = '<meta name="turbo-refresh-scroll" content="'.$turbo_refresh.'">';
        }
        $html = implode(PHP_EOL, $metas) ?? '';
        return $asMarkup
            ? Strings::markup(html: $html)
            : $html;
    }

    /**
     * Is Turbo-Frame request
     * @param Request|null $request
     * @return boolean
     */
    public function isTurboFrameRequest(?Request $request = null): bool
    {
        $request ??= $this->getCurrentRequest();
        return $request
            ? !empty($request->headers->get('Turbo-Frame'))
            : false;
    }

    /**
     * Is Turbo-Stream request
     * @param Request|null $request
     * @param boolean $prepareRequest
     * @return boolean
     */
    public function isTurboStreamRequest(
        ?Request $request = null,
        bool $prepareRequest = true,
    ): bool
    {
        $request ??= $this->getCurrentRequest();
        if(empty($request)) return false;
        $isTurbo = $request->getMethod() !== 'GET' && TurboBundle::STREAM_FORMAT === $request->getPreferredFormat();
        // if($isTurbo && !in_array(TurboBundle::STREAM_MEDIA_TYPE, $request->getAcceptableContentTypes())) {
        //     throw new Exception(vsprintf('That is disturbing... This is a Turbo request (Method is %s and format is %s), but acceptable content-type does not contain %s (has %s)', [$request->getMethod(), $request->getPreferredFormat(), TurboBundle::STREAM_MEDIA_TYPE, implode(', ', $request->getAcceptableContentTypes())]));
        // }
        if($isTurbo && $prepareRequest) $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
        return $isTurbo;
    }


    /************************************************************************************************************/
    /** ROUTES                                                                                                  */
    /************************************************************************************************************/

    public function getRoutes(): RouteCollection
    {
        return $this->get('router')->getRouteCollection();
    }
    
    public function routeExists(string $route, bool|array $control_generation = false): bool
    {
        $exists = $this->getRoutes()->get($route) !== null;
        if($control_generation) {
            try {
                $this->get('router')->generate($route, is_array($control_generation) ? $control_generation : []);
            } catch (\Throwable $th) {
                //throw $th;
                $exists = false;
            }
        }
        return $exists;
    }

    public function isCurrentRoute(
        string $route,
        mixed $param = null
    ): bool
    {
        // dump($this->getRoute(), $this->getRouteParams(), $param instanceof MenuInterface ? $param->getItems() : null);
        if($param instanceof WebpageInterface && $param->isPrefered() && $this->getRoute() == 'app_home') return true;
        if($route !== $this->getRoute()) return false;
        if(!empty($param)) {
            if($param instanceof SlugInterface) {
                if($param instanceof WebpageInterface) {
                    if($param->isPrefered() && empty($this->getRouteParams())) return true;
                }
                if($param instanceof MenuInterface) {
                    foreach ($param->getItems() as $item) {
                        if(in_array($item->getSlug(), $this->getRouteParams())) return true;
                    }
                }
                $param = $param->getSlug();
            }
            return in_array($param, $this->getRouteParams());
        }
        return true;
    }

    public function getRoute(): string
    {
        return $this->_route ??= $this->getCurrentRequest()?->attributes->get('_route');
    }

    public function getRouteParams(): mixed
    {
        return $this->_route_params ??= $this->getCurrentRequest()?->attributes->get('_route_params');
    }

    /**
     * Get URL of route only if can be generated
     * public const ABSOLUTE_URL = 0;
     * public const ABSOLUTE_PATH = 1;
     * public const RELATIVE_PATH = 2;
     * public const NETWORK_PATH = 3;
     *
     * @param string $route
     * @param array $parameters
     * @param [type] $referenceType
     * @return string|null
     */
    public function getUrlIfExists(
        string $route,
        array $parameters = [],
        int $referenceType = Router::ABSOLUTE_PATH,
    ): ?string
    {
        /** @var RouterInterface */
        $router = $this->get('router');
        $_route = $this->getRoute();
        // if(!$this->getRoutes()->get($route)) return null;

        // ? : avoid if is same as current route / includes logic security
        if(preg_match('/^\?+/', $route)) {
            $route = preg_replace('/^\?+/', '', $route);
            switch (true) {
                case preg_match('/login/', $route):
                    if(preg_match('/login/', $_route) || $this->getUser()) return null;
                    break;
                case preg_match('/logout/', $route):
                    if(preg_match('/logout/', $_route) || !$this->getUser()) return null;
                    break;
                default:
                    if($route === $_route) return null;
                    break;
            }
        }
        // // ! : avoid 
        // if($testB = preg_match('/^\!+/', $route)) {
        //     $route = preg_replace('/^\!+/', '', $route);
        //     switch (true) {
        //         case preg_match('/login/', $route):
        //             if($this->getUser()) return null;
        //             break;
        //         case preg_match('/logout/', $route):
        //             if(!$this->getUser()) return null;
        //             break;
        //         default:
        //             if($route === $_route) return null;
        //             break;
        //     }
        // }
        $url = null;
        try {
            $url = $router->generate(name: $route, parameters: $parameters, referenceType: $referenceType);
        } catch (\Throwable $th) {
            //throw $th;
        }
        // dump($url);
        return $url;
    }


    /************************************************************************************************************/
    /** CACHE                                                                                                   */
    /************************************************************************************************************/

    public function getCache(): CacheServiceInterface
    {
        return $this->get(CacheServiceInterface::class);
    }


    /************************************************************************************************************/
    /** PARAMETERS                                                                                              */
    /************************************************************************************************************/

    public function getParameterBag(): ParameterBagInterface
    {
        return $this->parameterBag;
    }

    public function getParam(
        string $name,
        array|bool|string|int|float|UnitEnum|null $default = null,
    ): array|bool|string|int|float|UnitEnum|null
    {
        return $this->getParameter($name, $default);
    }

    public function getParameter(
        string $name,
        array|bool|string|int|float|UnitEnum|null $default = null,
    ): array|bool|string|int|float|UnitEnum|null
    {
        if($this->parameterBag->has($name)) {
            try {
                return $this->parameterBag->get($name);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
        return $default;
    }

    /**
     * App Params must be set in BODY "data-app" attribute
     * @param boolean $asJson
     * @param string|null $filter
     * @return array|string
     */
    public function getAppParams(
        bool $asJson = false,
        ?string $filter = null
    ): array|string
    {
        if(!$this->hasAppContext()) throw new Exception(vsprintf('Error in %s line %d: context is not loaded!', [__METHOD__, __LINE__]));
        $params = $this->getFilteredParams($filter);
        $params = array_merge($params, $this->appContext->jsonSerialize());
        return $asJson
            ? json_encode($params)
            : $params;
    }

    protected function getFilteredParams(
        ?string $filter = null
    ): array
    {
        switch (true) {
            case is_string($filter) && !empty($filter):
                $keys = $this->getFilterKeys($filter);
                if(is_bool($keys)) {
                    return $keys ? $this->parameterBag->all() : [];
                }
                $params = [];
                foreach ($keys as $key) {
                    $params[$key] = $this->parameterBag->has($key) ? $this->parameterBag->get($key) : null;
                }
                return $params;
                break;
            default:
                return $this->parameterBag->all();
                break;
        }
    }

    public function identity(): Identity
    {
        $params_identity = $this->getParam(Identity::ENTREPRISE_PARAM_NAME, $this->getParamsByRegex(Identity::REGEX_FIND_ENTREPRISE));
        return $this->identity ??= new Identity($params_identity);
    }

    public function getParamsByRegex(string $regexp): array
    {
        return array_filter($this->parameterBag->all(), function($key) use ($regexp) {
            return preg_match($regexp, $key);
        }, ARRAY_FILTER_USE_KEY);
    }

    protected function getFilterKeys(
        ?string $filter = null
    ): array|bool
    {
        if(empty($filter)) return true;
        $filters = [
            'public_app' => [
                'kernel.charset',
                'kernel.debug',
                'kernel.default_locale',
                'kernel.enabled_locales',
                'kernel.environment',
                // 'liip_imagine.filter_sets',
                'custom_colors',
                'notif',
                'currency',
                'locale',
                'timezone',
                // 'mail_from',
                // 'mail_to_admin',
                // 'mail_to_dev',
                'router.request_context.host',
                'router.request_context.scheme',
                'asset.request_context.secure',
            ],
        ];
        return array_key_exists($filter, $filters)
            ? $filters[$filter]
            : false;
    }

    /**
     * SHORTCUTS
     */

    // Notif types
    public function getNotifTypes(): array
    {
        return $this->parameterBag->get('notif')['types'];
    }

    // Custom colors
    public function getCustomColors(): array
    {
        return $this->parameterBag->get('custom_colors');
    }

}