<?php
namespace Aequation\LaboBundle\EventSubscriber;

use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class SecuritySubscriber implements EventSubscriberInterface
{

    protected AppServiceInterface $appService;

    public function __construct(
        #[Autowire(service: 'service_container')]
        protected ContainerInterface $container,
    )
    {
        $this->appService = $this->container->get(AppServiceInterface::class);
    }


    public static function getSubscribedEvents(): array
    {
        return [
            SwitchUserEvent::class => 'onSwitchUser',
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onSwitchUser(SwitchUserEvent $event)
    {
        if($this->appService->isDev()) {
            dd('[DEV] USER SWITCHED!!!', $event);
        }
    }

    public function onLoginSuccess(LoginSuccessEvent $event)
    {
        $this->appService->updateContextUser($event);
    }

}