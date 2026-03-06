<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Component\AppContext;
use Aequation\LaboBundle\Service\Tools\Classes;
use Aequation\LaboBundle\Service\Tools\Strings;
use Aequation\LaboBundle\Component\AppContextTemp;
use Aequation\LaboBundle\Service\Base\BaseService;
use Aequation\LaboBundle\Model\Interface\MenuInterface;
use Aequation\LaboBundle\Model\Interface\SlugInterface;
use Aequation\LaboBundle\Model\Final\FinalWebpageInterface;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Model\Attribute\ClassCustomService;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Service\Interface\ServiceInterface;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Component\Interface\AppContextInterface;
use Aequation\LaboBundle\Service\Interface\CacheServiceInterface;
use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use Aequation\LaboBundle\Service\Interface\AppRoleHierarchyInterface;
// Symfony
use Doctrine\Common\Collections\Collection;
use Symfony\UX\Turbo\TurboBundle;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
// PHP
use Twig\Markup;
use Twig\Environment;
use Twig\Loader\LoaderInterface;
use UnitEnum;
use Exception;
use ArrayObject;
use DateTimeZone;
use DateTimeImmutable;

#[AsAlias(AppServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: false)]
class AppService extends BaseService implements AppServiceInterface
{

    public readonly ContainerInterface $container;
    protected ?FirewallConfig $firewallConfig;
    protected AppContextInterface $appContext;
    protected ?Request $request;
    protected ?Session $session;
    // protected Identity $identity;
    // protected string $project_dir;
    protected ?string $_route;
    protected mixed $_route_params;
    public array $__src = [];

    public function __construct(
        public readonly RequestStack $requestStack,
        public readonly KernelInterface $kernel,
        public readonly ParameterBagInterface $parameterBag,
        public readonly Security $security,
        public readonly AccessDecisionManagerInterface $accessDecisionManager,
        public readonly AuthorizationCheckerInterface $authorizationChecker,
        public readonly Environment $twig,
        public readonly NormalizerInterface $normalizer,
        public readonly SerializerInterface $serializer,
    ) {
        $this->container = $this->kernel->getContainer();
        // $this->project_dir = $this->kernel->getProjectDir();
        $this->surveyRecursion(__METHOD__, 5);
        $this->initializeAppContext();
    }


    /************************************************************************************************************/
    /** CONTAINER / SERVICES                                                                                    */
    /************************************************************************************************************/

    public function getContainer(): ContainerInterface
    {
        return $this->container ??= $this->kernel->getContainer();
    }

    /**
     * Has service (only if public)
     * @param string $id
     * @return bool
     */
    public function has(
        string $id
    ): bool
    {
        return $this->getContainer()->has($id);
        // return $this->getContainer()?->has($id) ?: false;
    }

    /**
     * Get service (only if public)
     * @param string $id
     * @param [type] $invalidBehavior
     * @return object|null
     */
    public function get(
        string $id,
        int $invalidBehavior = ContainerInterface::NULL_ON_INVALID_REFERENCE
    ): ?object
    {
        $this->surveyRecursion(__METHOD__);
        if($this->isDev()) {
            $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;
        }
        return $this->getContainer()->get($id, $invalidBehavior);
    }

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
        $this->surveyRecursion(__METHOD__);
        $serviceName = $this->getClassServiceName($objectOrClass);
        return $this->has($serviceName)
            ? $this->get($serviceName)
            : null;
    }


    /************************************************************************************************************/
    /** HOST                                                                                                    */
    /************************************************************************************************************/

    public function getHost(): ?string
    {
        return $this->getCurrentRequest()?->getHost() ?? null;
    }

    public function getWebsiteHost(?string $ext = null): ?string
    {
        $website_host = preg_replace('/^(www\.)/', '', $this->getParameter('router.request_context.host', ''));
        if(!empty($ext)) {
            $ext = preg_replace('/^\./', '', $ext);
            $website_host = preg_replace('#\.([a-z]{2,})$#', '.'.$ext, $website_host);
        }
        return empty($website_host) ? null : $website_host;
    }

    public function isLocalHost(): bool
    {
        $host = $this->getHost();
        $validHosts = [
            '127.0.0.1',
            'localhost',
        ];
        return in_array($host, $validHosts, true);
    }

    public function isProdHost(?array $countries = null): bool
    {
        if(empty($countries)) {
            $countries = ['com', 'fr'];
        }
        $host = $this->getHost();
        $website_host = $this->getWebsiteHost();
        $validHosts = [];
        foreach (array_unique($countries) as $country) {
            $country = strtolower(preg_replace('/^\./', '', $country));
            $validHosts[$country] = preg_replace('#\.('.implode('|', $countries).')$#', '.'.$country, $website_host);
            $validHosts['www|'.$country] = 'www.'.preg_replace('#\.('.implode('|', $countries).')$#', '.'.$country, $website_host);
        }
        return in_array($host, $validHosts, true);
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
    public final function initializeAppContext(
        ?SessionInterface $session = null
    ): bool
    {
        // $this->surveyRecursion(__METHOD__.'::line_'.__LINE__, 20);
        if($session instanceof SessionInterface && !($this->session instanceof SessionInterface)) {
            $this->session = $session;
        }
        if(!$this->hasAppContext(false)) {
            $this->surveyRecursion(__METHOD__.'::line_'.__LINE__, 5);
            // Try find session
            $session ??= $this->getSession();
            if($session instanceof SessionInterface) {
                $this->setAppContext(new AppContext($this, $session->get(AppService::CONTEXT_SESSNAME, null)));
            } else if(!isset($this->appContext)) {
                $this->setAppContext(new AppContextTemp($this, []));
            }
        }
        return $this->appContext->isValid();
    }

    private function setAppContext(
        AppContextInterface $appContext
    ): static
    {
        if($this->hasAppContext(false)) {
            throw new Exception(vsprintf('Warning! %s line %d: final AppContext is already defined!', [__METHOD__, __LINE__]));
        }
        $this->appContext = $appContext;
        return $this;
    }

    public final function getAppContext(): ?AppContextInterface
    {
        $this->surveyRecursion(__METHOD__);
        $this->initializeAppContext();
        return $this->appContext;
    }

    public final function hasAppContext(
        bool $try_initialize = true
    ): bool
    {
        $this->surveyRecursion(__METHOD__);
        if($try_initialize) $this->initializeAppContext();
        return isset($this->appContext) && $this->appContext->isFinalContext();
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
    /** MAIN ENTREPRISE                                                                                 */
    /****************************************************************************************************/

    public function getMainEntreprise(): ?Object
    {
        $this->surveyRecursion(__METHOD__);
        return $this->get('App\\Service\\Interface\\EntrepriseServiceInterface')->getMainEntreprise();
    }



    /****************************************************************************************************/
    /** NORMALIZER / SERIALIZER                                                                         */
    /****************************************************************************************************/

    public function getSerialized(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null
    {
        $this->surveyRecursion(__METHOD__);
        if($object instanceof Collection) $object = $object->toArray();
        if(is_array($object)) $object = array_values($object); // for React, can not be object, but array
        // $context[AbstractObjectNormalizer::ENABLE_MAX_DEPTH] = true;
        return $this->serializer->serialize($object, $format, $context);
    }

    public function getNormalized(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null
    {
        $this->surveyRecursion(__METHOD__);
        if($object instanceof Collection) $object = $object->toArray();
        if(is_array($object)) $object = array_values($object); // for React, can not be object, but array
        // $context[AbstractObjectNormalizer::ENABLE_MAX_DEPTH] = true;
        return $this->normalizer->normalize($object, $format, $context);
    }


    /************************************************************************************************************/
    /** DIRS                                                                                                    */
    /************************************************************************************************************/

    public function getProjectDir(
        bool $endSeparator = false,
    ): string
    {
        $this->surveyRecursion(__METHOD__);
        $project_dir = preg_replace('/\\'.DIRECTORY_SEPARATOR.'*$/', '', $this->kernel->getProjectDir());
        return $endSeparator ? $project_dir.DIRECTORY_SEPARATOR : $project_dir;
    }

    public function getDir(
        ?string $path = null,
        bool $endSeparator = false,
    ): string
    {
        $this->surveyRecursion(__METHOD__);
        $dir = $this->getProjectDir(true).ltrim(ltrim($path, DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        return $endSeparator ? $dir : preg_replace('/\\'.DIRECTORY_SEPARATOR.'*$/', '', $dir);
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
        $this->surveyRecursion(__METHOD__);
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
        $this->surveyRecursion(__METHOD__);
        return $this->get(LaboUserServiceInterface::class)->getMainSAdmin();
    }

    public function getMainAdmin(): ?LaboUserInterface
    {
        $this->surveyRecursion(__METHOD__);
        return $this->get(LaboUserServiceInterface::class)->getMainAdmin();
    }

    // protected function getAuthorizationChecker(): AuthorizationCheckerInterface
    // {
    //     return $this->authorizationChecker;
    // }

    // public function getAccessDescisionManager(): AccessDecisionManagerInterface
    // {
    //     return $this->accessDecisionManager;
    // }

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
        $this->surveyRecursion(__METHOD__);
        if($attributes instanceof LaboUserInterface) {
            $attributes->setRoleHierarchy($this->get(AppRoleHierarchyInterface::class));
            $attributes = $attributes->getHigherRole();
        }
        return $this->authorizationChecker->isGranted($attributes, $subject);
    }

    // public function isUserGrantedXXX(
    //     mixed $attributes,
    //     mixed $subject = null,
    //     LaboUserInterface $user = null,
    //     string $firewallName = null,
    // ): bool
    // {
    //     $user ??= $this->getUser();
    //     $firewallName ??= $this->appContext->getFirewallName();
    //     if($user instanceof LaboUserInterface) {
    //         $token = new UsernamePasswordToken($user, $firewallName, $user?->getRoles() ?: []);
    //         return $this->accessDecisionManager->decide($token, (array)$attributes, $subject);
    //     }
    //     if(!in_array($firewallName, static::PUBLIC_FIREWALLS)) return false;
    //     return $this->isGranted($attributes, $subject);
    // }

    /**
     * Is user granted for attributes
     * @see https://www.remipoignon.fr/symfony-comment-verifier-le-role-dun-utilisateur-en-respectant-la-hierarchie-des-roles/
     *
     * @param LaboUserInterface $user
     * @param [type] $attributes
     * @param [type] $object
     * @param string $firewallName = 'none'
     * @return boolean
     */
    public function isUserGranted(
        LaboUserInterface $user,
        $attributes,
        $object = null,
        string $firewallName = 'none'
    ): bool
    {
        $this->surveyRecursion(__METHOD__);
        if(empty($firewallName)) {
            $firewallName = 'none';
        }
        if(!in_array($firewallName, array_merge(['none'], static::PUBLIC_FIREWALLS))) {
            if($this->isDev()) {
                throw new Exception(vsprintf('Error %s line %d: could not determine user for firewall %s!', [__METHOD__, __LINE__, $firewallName]));
            }
            return false;
        }
        $attributes = (array)$attributes;
        $token = new UsernamePasswordToken($user, $firewallName, $user->getRoles());
        return $this->accessDecisionManager->decide($token, $attributes, $object);
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
        string $firewallName = 'none',
    ): bool
    {
        $this->surveyRecursion(__METHOD__);
        // return false;
        $user ??= $this->getUser();
        if(!($user instanceof LaboUserInterface)) {
            if($this->isDev()) {
                throw new Exception(vsprintf('Error %s line %d: could not determine user for action %s!', [__METHOD__, __LINE__, $action]));
            }
            return false;
        }
        return $this->isUserGranted($user, $action, $entity, $firewallName);
    }

    // /**
    //  * Checks if the attribute is granted against the current authentication token and optionally supplied subject.
    //  * @throws \LogicException
    //  */
    // public function isVoterGranted(mixed $attribute, mixed $subject = null): bool
    // {
    //     if (!$this->getContainer()->has('security.authorization_checker')) {
    //         throw new \LogicException('The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".');
    //     }
    //     return $this->getContainer()->get('security.authorization_checker')?->isGranted($attribute, $subject) ?? false;
    // }


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
        ?string $date = null
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
        $this->surveyRecursion(__METHOD__);
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
        $this->surveyRecursion(__METHOD__);
        return $this->request ??= $this->requestStack?->getCurrentRequest() ?: null;
    }

    public function getSession(): ?SessionInterface
    {
        $this->surveyRecursion(__METHOD__);
        return $this->session ??= static::getRequestSession($this->getCurrentRequest());
    }

    public static function getRequestSession(
        ?Request $request
    ): ?SessionInterface
    {
        return $request && $request->hasSession() ? $request->getSession() : null;
    }

    public function getRequestAttribute(string $name, mixed $default = null): mixed
    {
        $this->surveyRecursion(__METHOD__.'::'.$name);
        return $this->getCurrentRequest()?->attributes?->get($name, $default) ?? $default;
    }

    public function getRequestContext(): ?RequestContext
    {
        $this->surveyRecursion(__METHOD__);
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
        $this->surveyRecursion(__METHOD__);
        $this->getSession()->set($name, $data);
        return $this;
    }

    public function getSessionData(
        string $name,
        mixed $default,
    ): mixed
    {
        $this->surveyRecursion(__METHOD__);
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

    public function getTurboMetas(
        bool $asMarkup = true,
    ): string|Markup
    {
        $metas = [];
        // Turbo refresh
        // $turbo_refresh = $this->getParam('turbo-refresh-scroll', null);
        // if(!empty($turbo_refresh)) {
        //     $metas[] = '<meta name="turbo-refresh-scroll" content="'.$turbo_refresh.'">';
        // }
        // $metas[] = '<meta name="turbo-refresh-method" content="morph">';
        // Turbo prefetch
        // $metas[] = '<meta name="turbo-prefetch" content="false">';
        $html = implode(PHP_EOL, $metas);
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

    public function isXmlHttpRequest(
        ?Request $request = null,
    ): bool
    {
        $request ??= $this->getCurrentRequest();
        if(empty($request)) return false;
        return $request->headers->get('x-requested-with', null) === 'XMLHttpRequest';
    }


    /************************************************************************************************************/
    /** ROUTES                                                                                                  */
    /************************************************************************************************************/

    public function getRoutes(): RouteCollection
    {
        $this->surveyRecursion(__METHOD__);
        return $this->get('router')->getRouteCollection();
    }
    
    public function routeExists(string $route, bool|array $control_generation = false): bool
    {
        $this->surveyRecursion(__METHOD__.'::'.$route);
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
        $this->surveyRecursion(__METHOD__.'::'.$route);
        if($param instanceof FinalWebpageInterface && $param->isPrefered() && $this->getRoute() == 'app_home') return true;
        if($route !== $this->getRoute()) return false;
        if(!empty($param)) {
            if($param instanceof SlugInterface) {
                if($param instanceof FinalWebpageInterface) {
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
        $this->surveyRecursion(__METHOD__);
        return $this->_route ??= $this->getCurrentRequest()?->attributes->get('_route');
    }

    public function getRouteParams(): mixed
    {
        $this->surveyRecursion(__METHOD__);
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
        $this->surveyRecursion(__METHOD__.'::'.$route);
        /** @var RouterInterface */
        $router = $this->get('router');
        $_route = $this->getRoute();
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
        $url = null;
        try {
            $url = $router->generate(name: $route, parameters: $parameters, referenceType: $referenceType);
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $url;
    }


    /************************************************************************************************************/
    /** CACHE                                                                                                   */
    /************************************************************************************************************/

    public function getCache(): CacheServiceInterface
    {
        $this->surveyRecursion(__METHOD__);
        return $this->get(CacheServiceInterface::class);
    }


    /************************************************************************************************************/
    /** PARAMETERS                                                                                              */
    /************************************************************************************************************/

    public function getParameterBag(): ParameterBagInterface
    {
        $this->surveyRecursion(__METHOD__);
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
        $this->surveyRecursion(__METHOD__.'::'.$name);
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
        $this->surveyRecursion(__METHOD__);
        if(!$this->hasAppContext(true)) throw new Exception(vsprintf('Error in %s line %d: context is not loaded!', [__METHOD__, __LINE__]));
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
        $this->surveyRecursion(__METHOD__);
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

    // public function identity(): Identity
    // {
    //     $params_identity = $this->getParam(Identity::ENTREPRISE_PARAM_NAME, $this->getParamsByRegex(Identity::REGEX_FIND_ENTREPRISE));
    //     return $this->identity ??= new Identity($params_identity);
    // }

    public function getParamsByRegex(string $regexp): array
    {
        $this->surveyRecursion(__METHOD__.'::'.$regexp);
        return array_filter($this->parameterBag->all(), function($key) use ($regexp) {
            return preg_match($regexp, $key);
        }, ARRAY_FILTER_USE_KEY);
    }

    protected function getFilterKeys(
        ?string $filter = null
    ): array|bool
    {
        $this->surveyRecursion(__METHOD__.'::'.($filter ?? '__empty__'));
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
        $this->surveyRecursion(__METHOD__);
        return $this->parameterBag->get('notif')['types'];
    }

    // Custom colors
    public function getCustomColors(): array
    {
        $this->surveyRecursion(__METHOD__);
        return $this->parameterBag->get('custom_colors');
    }


    /**
     * Survey recursion in some methods (DEV only)
     * use: $this->surveyRecursion(__METHOD__.'::*somename*');
     * 
     * @param string $name
     * @param int|null $max
     * @return void
     */
    public function surveyRecursion(
        string $name,
        ?int $max = null
    ): void {
        if ($this->isDev()) {
            $max ??= 10000;
            $this->__src[$name] ??= 0;
            $this->__src[$name]++;
            if ($this->__src[$name] > $max) {
                throw new Exception(vsprintf('Error %s line %d: "%s" recursion limit %d reached!', [__METHOD__, __LINE__, $name, $max]));
            }
        }
    }

}