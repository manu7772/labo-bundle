<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Component\Interface\AppContextInterface;
use Aequation\LaboBundle\Entity\LaboUser;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use Aequation\LaboBundle\Service\Tools\HttpRequest;
use Symfony\Component\Routing\RequestContext;
use function Symfony\Component\String\u;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use IntlTimeZone;
use ReflectionClass;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Intl\Timezones;
use Symfony\Component\Routing\RouterInterface;

class AppContextTemp implements AppContextInterface
{

    public const IS_TEMP                                               = true;

    const DEV_ERROR_EXCEPTION = true;
    const DEV_WARNING_EXCEPTION = true;
    const USE_ATTR_GETTER = false;

    protected array                 $attrs;
    protected Security              $security;
    protected KernelInterface       $kernel;
    protected ?Request              $request;
    protected ?SessionInterface     $session;
    protected ?RouterInterface      $router;
    protected readonly   int        $timestamp;
    protected array                 $data                           = [];
    protected array                 $marks                          = [];
    // Context values
    protected ?string               $_environment                   = null;
    protected ?string               $_firewall                      = null;
    protected ?LaboUserInterface    $_user                          = null;
    protected ?DateTimeZone         $_timezone                      = null;
    protected ?DateTimeImmutable    $_datenow                       = null;
    protected string                $_dater;
    protected ?string               $_language                      = null;
    protected array                 $_languages                     = [];
    protected bool                  $_requestFrom_cli               = false;
    protected bool                  $_public                        = false;
    protected bool                  $_darkmode                      = true;
    protected ?RequestContext       $_request_context               = null;
    protected array                 $_request_context_history       = [];
    protected array                 $_headers                       = [];
    protected bool                  $_requestFrom_turbo_frame       = false;
    protected bool                  $_requestFrom_turbo_stream      = false;
    protected bool                  $_requestFrom_xml_http          = false; // x-requested-with = "XMLHttpRequest" ?

    public function isCliXmlHttpRequest(): bool
    {
        return
            $this->_requestFrom_xml_http
            || $this->_requestFrom_turbo_frame
            || $this->_requestFrom_turbo_stream
            || $this->_requestFrom_cli
            ;
    }

    public function __construct(
        protected AppServiceInterface $appService,
        protected ?array $initData,
    ) {
        $this->security = $this->appService->security;
        $this->kernel = $this->appService->kernel;
        $this->request = $this->appService->requestStack?->getCurrentRequest() ?? null;
        $this->session = $this->appService->getSession();
        $this->router = $this->appService->get('router');
        $this->timestamp = time();
        $this->getContextAttributes();
        $this->initialize();
    }

    public function isTempContext(): bool
    {
        return static::IS_TEMP;
    }

    public function isFinalContext(): bool
    {
        return !static::IS_TEMP;
    }

    protected function initialize(): bool
    {
        $this->marks = [
            static::MARK_DEPRECATION => [],
            static::MARK_WARNING => [],
            static::MARK_ERROR => [],
        ];
        /** @var ?FirewallConfig $fwc */
        $fwc = $this->getFirewallConfig();
        // Default values
        // $this->setInitData($this->initData);
        $this->_environment                 = $this->kernel->getEnvironment();
        $this->_firewall                    = $fwc?->getName() ?: null;
        $this->_user                        = $this->security->getUser();
        $this->_timezone                    = $this->_user ? $this->_user->getDateTimezone() : new DateTimeZone($this->appService->getParam('timezone'));
        $this->_dater                       = static::DEFAULT_DATER;
        $this->_datenow                     = new DateTimeImmutable($this->_dater, $this->_timezone);
        $this->_language                    = null;
        $this->_languages                   = [];
        $this->_requestFrom_cli             = HttpRequest::isCli();
        $this->_public                      = !empty($this->_firewall) && in_array(strtolower($this->_firewall), $this->appService::PUBLIC_FIREWALLS);
        $this->_darkmode                    = $this->getComputedDarkmode();
        $this->_request_context             = $this->router instanceof RouterInterface ? $this->router->getContext() : null;
        $this->_headers                     = $this->request ? $this->request->headers->all() : [];
        $this->_requestFrom_turbo_frame     = $this->appService->isTurboFrameRequest();
        $this->_requestFrom_turbo_stream    = $this->appService->isTurboStreamRequest();
        $this->_requestFrom_xml_http        = $this->appService->isXmlHttpRequest();
        $valid = $this->isValid(true);
        if(!$this->isTempContext() && !$this->_requestFrom_cli) {
            $serialized = $this->jsonSerialize();
            $this->appService->setSessionData($this->appService::CONTEXT_SESSNAME, $serialized);
        }
        return $valid;
    }

    public function update(): bool
    {
        $this->_user                        = $this->security->getUser();
        $this->_timezone                    = $this->_user ? new DateTimeZone($this->_user->getTimezone()) : new DateTimeZone($this->appService->getParam('timezone'));
        $this->_datenow                     = new DateTimeImmutable($this->_dater, $this->_timezone);
        $this->_language                    = null;
        $this->_languages                   = [];
        $this->_darkmode                    = $this->getComputedDarkmode();
        return $this->isValid(true);
    }

    public function setInitData(
        ?array $initData
    ): bool
    {
        $this->initData = $initData;
        foreach ((array)$this->initData as $name => $value) {
            $_name = preg_replace('/^_+/', '_', '_'.$name);
            if(array_key_exists($name, $this->attrs)) {
                switch ($name) {
                    // case 'is_temp':
                    // case 'classname':
                    //     // do nothing...
                    //     break;
                    case 'user':
                        $value = (int)$value;
                        if(empty($value)) {
                            $this->_user = null;
                        } else {
                            if($this->_user && $this->_user->getId() !== $value) {
                                // replace User
                                $this->_user = $this->appService->get(AppEntityManagerInterface::class)->getRepository(LaboUser::class)->find($value);
                                if($this->isDev() && empty($this->_user)) {
                                    $this->addError(vsprintf('%s line %d: could not find User of id %d!', [__METHOD__, __LINE__, $value]));
                                }
                            }
                        }
                        break;
                    case 'request_context_history':
                        $this->_request_context_history = [];
                        foreach ($value as $timestamp => $data) {
                            $this->addRequestContextHistory($timestamp, $this->createRequestContext($data));
                        }
                        break;
                    case 'request_context':
                        $this->addRequestContextHistory($value['timestamp'], $this->createRequestContext($value));
                        break;
                    case 'timezone':
                        try {
                            $this->$_name = new DateTimeZone($value);
                        } catch (\Throwable $th) {
                            //throw $th;
                            $this->addError(vsprintf('%s line %d: could not construct DateTimeZone with %s (%s)!', [__METHOD__, __LINE__, json_encode($value), $th->getMessage()]));
                            $this->$_name = new DateTimeZone($this->appService->getParam('timezone'));
                        }
                        break;
                    case 'datenow':
                        $this->$_name = new DateTimeImmutable($value);
                        break;
                    default:
                        $this->$_name = $value;
                        break;
                }
            }
        }
        return $this->isValid(true);
    }

    protected function getContextAttributes(): array
    {
        if(!isset($this->attrs)) {
            $rc = new ReflectionClass($this);
            foreach ($rc->getProperties() as $property) {
                if(preg_match('/^_\w+$/', $property->name)) {
                    $name = preg_replace('/^_/', '', $property->name);
                    $this->attrs[$name] = $property;
                }
            }
        }
        // var_dump(static::class.' --------------------------------------------------------- '.($this->isTempContext() ? 'TEMP' : 'FINAL').' / '.spl_object_hash($this));
        // echo('<br/>'.$this->appService::class);
        // echo('<br/>');
        // var_dump($this->attrs);
        // echo('<br/><br/>');
        return $this->attrs;
    }

    public function jsonSerialize(): mixed
    {
        $this->data = [];
        foreach ($this->attrs as $propname => $property) {
            $_property_name = $property->name;
            $getter = u('get'.$_property_name)->camel();
            if(static::USE_ATTR_GETTER && method_exists($this, $getter)) {
                $this->data[$propname] = $this->$getter();
                continue;
            }
            switch ($propname) {
                case 'request_context_history':
                    $rchdata = [];
                    foreach ($this->_request_context_history as $timestamp => $rc) {
                        /** @var RequestContext $rc */
                        $rchdata[$timestamp] = $this->getRequestContextAsArray($rc);
                    }
                    $this->data[$propname] = $rchdata;
                    break;
                case 'request_context':
                    $rcdata = $this->getRequestContextAsArray($this->{$_property_name});
                    $rcdata['timestamp'] = $this->timestamp;
                    $this->data[$propname] = $rcdata;
                    break;
                case 'user':
                    $user = $this->{$_property_name};
                    $this->data[$propname] = $user ? $user->getId() : null;
                    break;
                case 'datenow':
                    $now = $this->{$_property_name};
                    $this->data[$propname] = $now->format(DATE_ATOM);
                    break;
                case 'timezone':
                    /** @var DateTimeZone $tz */
                    $tz = $this->{$_property_name};
                    $this->data[$propname] = $tz->getName();
                    break;
                // case 'firewall':
                //     $this->data[$propname] = $this->getFirewallName();
                //     break;
                default:
                    $this->data[$propname] = $this->{$_property_name};
                    break;
            }
        }
        $this->data['is_temp'] = $this->isTempContext();
        $this->data['classname'] = static::class;
        $this->data['has_session'] = $this->request ? $this->request->hasSession() : false;
        return $this->data;
    }

    /**
     * Get a string representation of this AppContext
     * @return string
     */
    public function getDumped(): string
    {
        $dump = '';
        foreach ($this->jsonSerialize() as $key => $value) {
            if(is_array($value)) {
                $subdump = '';
                foreach ($value as $subkey => $subval) {
                    if(!is_string($subval)) $subval = json_encode($subval);
                    $subdump .= PHP_EOL.'   - '.$subkey.' = '.$subval;
                }
                $value = $subdump;
            }
            if(!is_string($value)) {
                $value = json_encode($value);
            }
            $dump .= PHP_EOL.$key.' = '.$value;
        }
        return $dump;
    }

    public function get(string $name): mixed
    {
        $name = u('get_'.$name)->camel();
        return $this->$name();
    }

    public function set(string $name, mixed $value): static
    {
        $name = u('set_'.$name)->camel();
        $this->$name($value);
        return $this;
    }
    
    public function reset(
        string $name,
        bool $useInitData = true
    ): static
    {
        $model = new static($this->appService, $useInitData ? $this->initData : []);
        $value = $model->get($name);
        unset($model);
        $name = '_'.$name;
        $this->$name = $value;
        return $this;
    }

    public function resetAll(
        bool $useInitData = true
    ): static
    {
        if(!$useInitData) {
            $memo = $this->initData;
            $this->initData = null;
            $this->initialize();
            $this->initData = $memo;
        } else {
            $this->initialize();
        }
        return $this;
    }


    /********************************************************************************************************************************************************
     *** DARKMODE
     ********************************************************************************************************************************************************/

    public function getDarkmodeClass(): string
    {
        return $this->getDarkmode() ? 'dark' : '';
    }

    public function getDarkmode(): bool
    {
        return $this->_darkmode;
    }

    protected function getComputedDarkmode(): bool
    {
        if($this->_user instanceof LaboUserInterface) return $this->_user->isDarkmode();
        $default_darkmode = $this->appService->getParam('darkmode', true);
        return $this->session?->get('darkmode', $default_darkmode) ?: $default_darkmode;
    }

    public function setDarkmode(bool $darkmode): bool
    {
        if($this->_user instanceof LaboUserInterface) {
            if($this->_user->isDarkmode() !== $darkmode) {
                $this->_user->setDarkmode($darkmode);
                $userService = $this->get(LaboUserServiceInterface::class);
                $userService->save($this->_user);
            }
            $this->session->set('darkmode', $this->_user->isDarkmode());
        } else if($this->session) {
            $this->session->set('darkmode', $darkmode);
        } else {
            // No User, No session... should be in CLI mode?
            if(!$this->_requestFrom_cli) {
                $this->addWarning(vsprintf('Error %s line %d: not CLI but has no session!!', [__METHOD__, __LINE__]));
            }
        }
        $this->_darkmode = $darkmode;
        return $this->getDarkmode();
    }

    public function switchDarkmode(): bool
    {
        return $this->setDarkmode(!$this->getDarkmode());
    }


    /********************************************************************************************************************************************************
     *** DATES & TIMES
     *** @see Symfony\Component\Form\Extension\Core\Type\TimezoneType
     ********************************************************************************************************************************************************/

    public function getDater(): string
    {
        return $this->_dater;
    }

    public function getDatenow(): DateTimeImmutable
    {
        return $this->_datenow;
    }

    public function getTimezones(
        string $input = 'datetimezone' // ['string', 'datetimezone', 'intltimezone']
    ): array
    {
        return static::getPhpTimezones($input);
    }

    // public function getAllRegions(): int
    // {
    //     return DateTimeZone::ALL;
    // }

    public function getTimezone(): DateTimeZone
    {
        return $this->_timezone;
    }

    public function setTimezone(
        string|DateTimeZone $timezone
    ): static
    {
        if(is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        }
        $this->_timezone = $timezone;
        return $this;
    }

    protected static function getPhpTimezones(string $input): array
    {
        $timezones = [];
        foreach (DateTimeZone::listIdentifiers(DateTimeZone::ALL) as $timezone) {
            if ('intltimezone' === $input && 'Etc/Unknown' === IntlTimeZone::createTimeZone($timezone)->getID()) continue;
            $timezones[str_replace(['/', '_'], [' / ', ' '], $timezone)] = $timezone;
        }
        return $timezones;
    }

    protected static function getIntlTimezones(string $input, ?string $locale = null): array
    {
        $timezones = array_flip(Timezones::getNames($locale));
        if ('intltimezone' === $input) {
            foreach ($timezones as $name => $timezone) {
                if ('Etc/Unknown' === IntlTimeZone::createTimeZone($timezone)->getID()) {
                    unset($timezones[$name]);
                }
            }
        }
        return $timezones;
    }


    /********************************************************************************************************************************************************
     *** ENVIRONMENT
     ********************************************************************************************************************************************************/

    public function isDev(): bool
    {
        return $this->_environment === 'dev';
    }

    public function isProd(): bool
    {
        return $this->_environment === 'prod';
    }

    public function isTest(): bool
    {
        return $this->_environment === 'test';
    }

    public function getEnvironment(): string
    {
        return $this->_environment;
    }


    /********************************************************************************************************************************************************
     *** REQUEST CONTEXT HISTORY
     ********************************************************************************************************************************************************/

    protected function addRequestContextHistory(
        int $timestamp,
        RequestContext $requestContext
    ): static
    {
        $this->_request_context_history[$timestamp] = $requestContext;
        krsort($this->_request_context_history);
        if(count($this->_request_context_history) > static::REQUEST_CONTEXT_HISTORY_LIMIT) {
            $this->_request_context_history = array_slice($this->_request_context_history, 0, static::REQUEST_CONTEXT_HISTORY_LIMIT);
        }
        return $this;
    }

    public function getRequestContextHistory(): array
    {
        return $this->_request_context_history;
    }

    protected function createRequestContext(array $data): RequestContext
    {
        $rc = new RequestContext(
            $data['baseUrl'] ?? null,
            $data['method'] ?? null,
            $data['host'] ?? null,
            $data['scheme'] ?? null,
            $data['httpPort'] ?? null,
            $data['httpsPort'] ?? null,
            $data['path'] ?? null,
            $data['queryString'] ?? null
        );
        if(isset($data['parameters']) && !empty($data['parameters'])) {
            $rc->setParameters($data['parameters']);
        }
        return $rc;
    }

    protected function getRequestContextAsArray(
        RequestContext $rc
    ): array
    {
        return [
            'baseUrl' => $rc->getBaseUrl(),
            'method' => $rc->getMethod(),
            'host' => $rc->getHost(),
            'scheme' => $rc->getScheme(),
            'httpPort' => $rc->getHttpPort(),
            'httpsPort' => $rc->getHttpsPort(),
            'path' => $rc->getPathInfo(),
            'queryString' => $rc->getQueryString(),
            // 'parameters' => $rc->getParameters(),
            'secure' => $rc->isSecure(),
        ];
    }


    /********************************************************************************************************************************************************
     *** VALID / COMPILE / COMMENTS / DEPRECATIONS / WARNINGS / ERRORS
     ********************************************************************************************************************************************************/

    public function isValid(
        bool $compile = false
    ): bool
    {
        if($compile) $this->compileErrors();
        return empty($this->marks[static::MARK_ERROR]);
    }

    protected function compileErrors(): static
    {
        // Compile errors here...
        if(!($this->session instanceof SessionInterface)) $this->addError(vsprintf('Error %s line %d: session not found!', [__METHOD__, __LINE__]));
        $fws = $this->getFirewalls();
        if(!in_array($this->_firewall, $fws)) $this->addError(vsprintf('Error %s line %d: firewall "%s" not found!', [__METHOD__, __LINE__, $this->_firewall]));
        return $this;
    }

    protected function addDeprecation(
        string $message
    ): static
    {
        $this->marks[static::MARK_DEPRECATION][] = $message;
        return $this;
    }

    public function getDeprecations(): array
    {
        return $this->marks[static::MARK_DEPRECATION];
    }

    public function hasDeprecations(): bool
    {
        return count($this->marks[static::MARK_DEPRECATION]) > 0;
    }

    protected function addWarning(
        string $message
    ): static
    {
        if(static::DEV_WARNING_EXCEPTION && !$this->isTempContext() && $this->isDev()) throw new Exception($message);
        $this->marks[static::MARK_WARNING][] = $message;
        return $this;
    }

    public function getwarnings(): array
    {
        return $this->marks[static::MARK_WARNING];
    }

    public function haswarnings(): bool
    {
        return count($this->marks[static::MARK_WARNING]) > 0;
    }

    protected function addError(
        string $message
    ): static
    {
        if(static::DEV_ERROR_EXCEPTION && !$this->isTempContext() && $this->isDev()) throw new Exception($message);
        $this->marks[static::MARK_ERROR][] = $message;
        return $this;
    }

    public function getErrors(): array
    {
        return $this->marks[static::MARK_ERROR];
    }

    public function hasErrors(): bool
    {
        return count($this->marks[static::MARK_ERROR]) > 0;
    }


    /********************************************************************************************************************************************************
     *** FIREWALLS / SECURITY
     ********************************************************************************************************************************************************/

    public function getUser(): ?LaboUserInterface
    {
        return $this->_user;
    }

    public function getPublic(): bool
    {
        return $this->isPublic();
    }

    public function isPublic(): bool
    {
        return $this->_public;
    }

    public function isPrivate(): bool
    {
        return !$this->_public;
    }

    public function getFirewall(): string
    {
        return $this->_firewall;
    }

    // public function setFirewall(string $firewall): static
    // {
    //     $this->_firewall = $firewall;
    //     return $this;
    // }

    public function getFirewallConfig(): ?FirewallConfig
    {
        return $this->request ? $this->security->getFirewallConfig($this->request) : null;
    }

    public function getFirewallName(): string
    {
        return $this->_firewall;
    //     $firewallConfig ??= empty($this->request) ? null : $this->security->getFirewallConfig($this->request);
    //     return $firewallConfig instanceof FirewallConfig
    //         ? $firewallConfig->getName()
    //         : null;
    //     // OLD METHOD / 
    //     // $firewall = $this->getRequestAttribute('_firewall_context');
    //     // if($this->isDev() && empty($firewall)) {
    //     //     // DEV ALERT
    //     //     dd($this->getFirewalls(), vsprintf('Error %s line %d: could not determine firewall name : got %s!', [__METHOD__, __LINE__, json_encode($firewall)]), $firewall, $this->security, $this->getRequest(), $this->getCurrentRequest()?->attributes?->all() ?? '<no attributes>');
    //     // }
    //     // return $fullname
    //     //     ? $firewall
    //     //     : u($firewall)->afterLast('.');
    }

    public function getFirewalls(): array
    {
        return $this->appService->getParameter('security.firewalls');
    }

    public function getMainFirewalls(): array
    {
        $firewalls = $this->getFirewalls();
        return array_filter($firewalls, fn($fw) => !in_array($fw, $this->appService::EXCLUDED_FIREWALLS));
    }

    public function getFirewallChoices(
        bool $onlyMains = true,
    ): array
    {
        $firewalls = $onlyMains
            ? $this->getMainFirewalls()
            : $this->getFirewalls();
        return array_combine($firewalls, $firewalls);
    }

}