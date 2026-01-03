<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Component\Interface\AppContextInterface;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Service\Interface\LaboAppVariableInterface;
use Aequation\LaboBundle\Service\Interface\LaboBundleServiceInterface;
// Symfony
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\LocaleSwitcher;
// PHP
use DateTime;

class LaboAppVariable extends AppVariable implements LaboAppVariableInterface
{

    public readonly array $symfony;
    public readonly array $php;
    public readonly AppServiceInterface $service;
    // public readonly AppContextInterface $appContext;

    public function __construct(
        AppServiceInterface $service,
        // #[Autowire(service: 'service_container')]
        // protected ContainerInterface $container,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
        public readonly KernelInterface $kernel,
        // ParameterBagInterface $parameterBag,
        // Security $security,
        LocaleSwitcher $localeSwitcher,
    )
    {
        $this->setTokenStorage($tokenStorage);
        $this->setRequestStack($requestStack);
        $this->setEnvironment($kernel->getEnvironment());
        $this->setDebug($kernel->isDebug());
        $this->setLocaleSwitcher($localeSwitcher);
        $this->setEnabledLocales(['fr_FR']);

        /** Service */
        $this->service = $service->getAppContext()->isPublic()
            ? $service
            : $service->get(LaboBundleServiceInterface::class);
        /** @var AppContextInterface */
        // $this->appContext = $this->service->getAppContext();
        /** @var App/Kernel $kernel */
        $eom = explode('/', $kernel::END_OF_MAINTENANCE);
        $END_OF_MAINTENANCE = new DateTime($eom[1].'-'.$eom[0].'-01');
        $eol = explode('/', $kernel::END_OF_LIFE);
        $END_OF_LIFE = new DateTime($eol[1].'-'.$eol[0].'-01');
        $this->symfony = [
            'VERSION' => $kernel::VERSION,
            'SHORT_VERSION' => $kernel::MAJOR_VERSION.'.'.$kernel::MINOR_VERSION,
            'VERSION_ID' => $kernel::VERSION_ID,
            'MAJOR_VERSION' => $kernel::MAJOR_VERSION,
            'MINOR_VERSION' => $kernel::MINOR_VERSION,
            'RELEASE_VERSION' => $kernel::RELEASE_VERSION,
            'EXTRA_VERSION' => $kernel::EXTRA_VERSION,
            'END_OF_MAINTENANCE' => $END_OF_MAINTENANCE,
            'END_OF_MAINTENANCE_TEXT' => $END_OF_MAINTENANCE->format('d/m/Y'),
            'END_OF_LIFE' => $END_OF_LIFE,
            'END_OF_LIFE_TEXT' => $END_OF_LIFE->format('d/m/Y'),
        ];
        // PHP INFO / in MB : memory_get_usage() / 1048576
        $this->php = [
            'version' => phpversion(),
            'PHP_VERSION_ID' => PHP_VERSION_ID,
            'PHP_EXTRA_VERSION' => PHP_EXTRA_VERSION,
            'PHP_MAJOR_VERSION' => PHP_MAJOR_VERSION,
            'PHP_MINOR_VERSION' => PHP_MINOR_VERSION,
            'PHP_RELEASE_VERSION' => PHP_RELEASE_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'date.timezone' => ini_get('date.timezone'),
        ];

    }


    public function getHost(): ?string
    {
        return $this->service->getHost();
    }

    public function isLocalHost(): bool
    {
        return $this->service->isLocalHost();
    }

    public function isProdHost(?array $countries = null): bool
    {
        return $this->service->isProdHost($countries);
    }

    // public function getRouter(): RouterInterface
    // {
    //     return $this->service->get('router');
    // }

}
