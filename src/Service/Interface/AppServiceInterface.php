<?php
namespace Aequation\LaboBundle\Service\Interface;

use Aequation\LaboBundle\Component\Interface\AppContextInterface;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;

use Twig\Markup;
use UnitEnum;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

interface AppServiceInterface extends ServiceInterface
{

    public const CONTEXT_SESSNAME = 'app_context';
    public const PUBLIC_FIREWALLS = ['main'];
    public const EXCLUDED_FIREWALLS = ['dev','tmp','image_resolver','uploads','secured_area'];

    public function has(string $id): bool;
    public function get(string $id, int $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE): ?object;
    public static function getClassServiceName(string|AppEntityInterface $objectOrClass): ?string;
    public function getClassService(string|AppEntityInterface $objectOrClass): ?ServiceInterface;
    // Host
    public function getHost(): ?string;
    public function getWebsiteHost(?string $ext = null): ?string;
    public function isLocalHost(): bool;
    public function isProdHost(?array $countries = null): bool;
    // Twig
    public function getTwig(): Environment;
    public function getTwigLoader(): LoaderInterface;
    // public function initContext(): static;
    // AppContext
    public function initializeAppContext(?SessionInterface $session = null): bool;
    public function getAppContext(): ?AppContextInterface;
    public function hasAppContext(): bool;
    public function getMainEntreprise(): ?Object;
    // Normalizer/Serializer
    public function getSerialized(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null;
    public function getNormalized(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null;
    // Dirs
    public function getProjectDir(bool $endSeparator = false): string;
    public function getDir(?string $path = null, bool $endSeparator = false): string|false;
    // Environment
    public function isDev(): bool;
    public function isProd(): bool;
    public function isTest(): bool;
    public function getEnvironment(): string;
    public function getUser(): ?LaboUserInterface;
    public function updateContextUser(LoginSuccessEvent $event): static;
    public function getMainSAdmin(): ?LaboUserInterface;
    public function getMainAdmin(): ?LaboUserInterface;
    public function isGranted(mixed $attributes, mixed $subject = null): bool;
    // public function isUserGranted(mixed $attributes, mixed $subject = null, LaboUserInterface $user = null, string $firewallName = null): bool;
    public function isUserGranted(LaboUserInterface $user, $attributes, $object = null, string $firewallName = 'none'): bool;
    public function isValidForAction(AppEntityInterface $entity, string $action, ?LaboUserInterface $user = null, string $firewallName = 'none'): bool;
    // Date time zone
    public function getCurrentTimezone(bool $asString = false): string|DateTimeZone;
    public function getDatetimeTimezone(string $date = 'NOW'): DateTimeImmutable;
    public function getCurrentDatetime(): DateTimeImmutable;
    // String
    public function getStringEncoder(): string;
    // Request/Session
    public function getCurrentRequest(): ?Request;
    public function getSession(): ?SessionInterface;
    public function getRequestAttribute(string $name, mixed $default = null): mixed;
    public function getRequestContext(): ?RequestContext;
    public function setSessionData(string $name, mixed $data): static;
    public function getSessionData(string $name, mixed $default): mixed;
    // Firewall
    // public function getFirewalls(): array;
    // public function getMainFirewalls(): array;
    // public function getFirewallChoices(bool $onlyMains = true): array;
    // public function getFirewallName(): ?string;
    // public function isPublic(): bool;
    // public function isPrivate(): bool;
    // Turbo
    public function getTurboMetas(bool $asMarkup = true): string|Markup;
    public function isTurboFrameRequest(?Request $request = null): bool;
    public function isTurboStreamRequest(?Request $request = null, bool $prepareRequest = true): bool;
    public function isXmlHttpRequest(?Request $request = null): bool;
    // Entities
    // public function isPersisted(AppEntityInterface|string $entity): bool;
    // public function isNotPersisted(AppEntityInterface|string $entity): bool;
    // Routes
    public function getRoutes(): RouteCollection;
    public function routeExists(string $route): bool;
    public function getUrlIfExists(string $route, array $parameters = [], int $referenceType = Router::ABSOLUTE_PATH): ?string;
    // Cache
    public function getCache(): CacheServiceInterface;
    // Parameters
    public function getParameterBag(): ParameterBagInterface;
    public function getParam(string $name, array|bool|string|int|float|UnitEnum|null $default = null): array|bool|string|int|float|UnitEnum|null;
    public function getParameter(string $name, array|bool|string|int|float|UnitEnum|null $default = null): array|bool|string|int|float|UnitEnum|null;
    public function getAppParams(bool $asJson = false, ?string $filter = null): array|string;
    // Notify
    public function getNotifTypes(): array;
    public function getCustomColors(): array;
    // Darkmode
    // public function getDarkmodeClass(): string;
    // public function getDarkmode(): bool;
    // public function setDarkmode(bool $darkmode): bool;
    // public function switchDarkmode(): bool;

}