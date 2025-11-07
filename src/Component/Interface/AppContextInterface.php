<?php
namespace Aequation\LaboBundle\Component\Interface;

use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use DateTimeImmutable;
use DateTimeZone;
use JsonSerializable;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Component\Routing\RequestContext;

interface AppContextInterface extends JsonSerializable
{

    // public const EXERCISE_DEFAULT                                      = 'default';
    // public const EXERCISE_FIXTURES                                     = 'fixtures';
    // public const EXERCISE_BASICS                                       = 'basics';
    // public const EXERCISE_FORM_CHOICE                                  = 'form_choice';

    public const REQUEST_CONTEXT_HISTORY_LIMIT                         = 8;

    public const MARK_DEPRECATION                                      = 'deprecation';
    public const MARK_WARNING                                          = 'warning';
    public const MARK_ERROR                                            = 'error';

    public const DEFAULT_DATER                                         = 'NOW';
    // public const IS_TEMP                                               = false;


    public function update(): bool;
    public function jsonSerialize(): mixed;
    public function getDumped(): string;
    public function get(string $name): mixed;
    public function set(string $name, mixed $value): static;
    public function reset(string $name): static;
    public function resetAll(): static;

    // Temp/Final context
    public function isTempContext(): bool;
    public function isFinalContext(): bool;
    public function isCliXmlHttpRequest(): bool;

    // Darkmode
    public function getDarkmodeClass(): string;
    public function getDarkmode(): bool;
    public function setDarkmode(bool $darkmode): bool;
    public function switchDarkmode(): bool;

    // Dates & Times
    public function getDater(): string;
    public function getDatenow(): DateTimeImmutable;
    public function getTimezones(string $input = 'datetimezone' /** ['string', 'datetimezone', 'intltimezone'] */): array;
    public function getTimezone(): DateTimeZone;
    public function setTimezone(string|DateTimeZone $timezone): static;

    // Environment
    public function isDev(): bool;
    public function isProd(): bool;
    public function isTest(): bool;
    public function getEnvironment(): string;

    // request context history
    public function getRequestContextHistory(): array;

    // validation
    public function isValid(bool $compile = false): bool;
    public function getDeprecations(): array;
    public function hasDeprecations(): bool;
    public function getwarnings(): array;
    public function haswarnings(): bool;
    public function getErrors(): array;
    public function hasErrors(): bool;

    // Security
    public function getUser(): ?LaboUserInterface;
    public function getPublic(): bool;
    public function isPublic(): bool;
    public function isPrivate(): bool;
    public function getFirewall(): string;
    public function getFirewallConfig(): ?FirewallConfig;
    public function getFirewallName(): ?string;
    public function getFirewalls(): array;
    public function getMainFirewalls(): array;
    public function getFirewallChoices(bool $onlyMains = true): array;

    // Request context history
    // protected function addRequestContextHistory(int $timestamp, RequestContext $requestContext): static;
    // protected function createRequestContext(array $data): RequestContext;

}