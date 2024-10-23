<?php
namespace Aequation\LaboBundle\EventSubscriber;

use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Service\AppService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Twig\Environment;
use function Symfony\Component\String\u;

use DateTime;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;

class LaboAppGlobalSubscriber implements EventSubscriberInterface
{
    public const DEFAULT_ERROR_TEMPLATE = 'exception/all.html.twig';
    public const TEST_PASSED_NAME = 'test_passed';

    protected AppService $appService;
    protected RouterInterface $router;

    public function __construct(
        #[Autowire(service: 'service_container')]
        protected ContainerInterface $container,
    )
    {
        $this->appService = $this->container->get(AppServiceInterface::class);
        $this->router = $this->container->get('router');
    }

    /**
     * Get subscribed Events
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
            KernelEvents::EXCEPTION => 'onExceptionEvent',
            KernelEvents::CONTROLLER => 'onController',
            // KernelEvents::RESPONSE => 'onKernelResponse',
            // KernelEvents::FINISH_REQUEST => 'onFinishRequest',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if(!$event->isMainRequest()) return;
        // $main_host = $this->appService->getParam('router.request_context.host');
        // dd($main_host); // (eg. vetoalliance.com)
        $this->initAppContext($event);

        // $hosts = ['localhost2', $main_host];
        // if(!preg_match('/^'.implode('|', $hosts).'/', $event->getRequest()->getHost(), $matches)) {
        //     // dd($matches, $event->getRequest()->getHost(), $event->getRequest()->getHttpHost());
        //     // LOGIN required
        //     if(!$this->appService->isGranted('ROLE_COLLABORATOR')) {
        //         dd($matches, $event->getRequest()->getHost(), $event->getRequest()->getHttpHost());
        //     }
        // }
    }

    public function onExceptionEvent(ExceptionEvent $event): void
    {
        // Disable control
        if(!$this->appService->isProd()) return;
        if($event->getRequest()->query->get('debug', 0) === "1") {
            return;
        }
        // Redirect to Exception Twig page
        /** @var Throwable */
        $exception = $event->getThrowable();
        $statusCode = 500;
        if(method_exists($exception, 'getCode') &&  $exception->getCode() > 0) {
            $statusCode = $exception->getCode();
        } else if(method_exists($exception, 'getStatusCode') &&  $exception->getStatusCode() > 0) {
            $statusCode = $exception->getStatusCode();
        }
        switch (true) {
            case $statusCode >= 100:
                $twigpage_name = $this->getTemplateName($statusCode);
                break;
            // case $exception instanceof HttpExceptionInterface:
            //     $twigpage_name = $this->getTemplateName($statusCode);
            //     break;
            // case $exception instanceof Error:
            //     $twigpage_name = $this->getTemplateName($statusCode);
            //     break;
            // case $exception instanceof LogicException:
            //     $twigpage_name = $this->getTemplateName($statusCode);
            //     break;
            default:
                $twigpage_name = static::DEFAULT_ERROR_TEMPLATE;
                break;
        }
        $context ??= ['exception' => $exception, 'exception_classname' => $exception::class, 'event' => $event, 'twigpage_name' => u($twigpage_name)->afterLast('/'), 'exceptionEvent' => $event];
        $response ??= $this->appService->twig->render(name: $twigpage_name, context: $context);
        // if($statusCode <= 0) dd($exception, $response);
        $event->setResponse(new Response($response, $statusCode));
    }

    protected function getTemplateName(
        string|int $statusCode
    ): string
    {
        $name = 'exception/'.$statusCode.'.html.twig';
        return $this->appService->getTwigLoader()->exists($name)
            ? $name
            : static::DEFAULT_ERROR_TEMPLATE;
    }

    // public function onKernelResponse(ResponseEvent $event): void
    // {
    //     if($this->appService->isDev()) {
    //         dump($this->appService->getContext(), $this->appService->getSession());
    //     }
    // }

    // public function onFinishRequest(FinishRequestEvent $event): void
    // {
    //     if($this->appService->isDev()) {
    //         dump($this->appService->getContext(), $this->appService->getSession());
    //     }
    // }

    public function onController(ControllerEvent $event): void
    {
        if(!$event->isMainRequest()) return;
        $this->initAppContext($event);
        // $event->getRequest()->getSession()->set(static::TEST_PASSED_NAME, false);
        // dd($this->appService->getRoute(), $this->appService->getParameter('lauch_website', null));
        /**
         * @see https://stackoverflow.com/questions/67115605/how-to-redirect-from-a-eventsubscriber-in-symfony-5
         */
        if($this->appService->getParameter('host_security_enabled', false) && !$this->appService->isGranted('ROLE_EDITOR')) {
            $controller = $this->getControllerObjectFromEvent($event);
            if($controller instanceof AbstractController) {
                $host = $event->getRequest()->getHost();
                $website_host = preg_replace('/^(www\.)/', '', $this->appService->getParameter('router.request_context.host', []));

                // **********************************
                // TEST/DEMO WEBSITES RESTRICTED AREA
                // **********************************
                $validHosts = [
                    '127.0.0.1',
                    'localhost',
                    $website_host,
                    'www.'.$website_host,
                ];
                if(!in_array($host, $validHosts) && $this->isAvailableRouteFor('demotest')) {
                    // Test or Demo Website / Restricted AREA
                    $post_pwd = $event->getRequest()->request->get('demo_password', null);
                    $passwd = $this->appService->getParameter('host_security_passwd', null);
                    $passed = $event->getRequest()->getSession()->get(static::TEST_PASSED_NAME, false);
                    if(empty($passwd) || $passed) return;
                    if($post_pwd === $passwd) {
                        $event->getRequest()->getSession()->set(static::TEST_PASSED_NAME, true);
                    } else if(!$passed) {
                        $event->setController(function () {
                            $response = $this->appService->twig->render(name: '@AequationLabo/security/test_website.html.twig');
                            return new Response($response, 403);
                        });
                    }
                }

                // **********************************
                // COUNTDOWN/LAUNCH WEBSITES
                // **********************************
                $validHosts = [
                    // '127.0.0.1',
                    // 'localhost',
                    $website_host,
                    'www.'.$website_host,
                ];
                // $firewall = $this->appService->getFirewallName();
                // dump($this->appService->getRoute(), $this->appService->getParameter('lauch_website', null));
                $context = $this->appService->getParameter('lauch_website', null);
                if(in_array($host, $validHosts) && empty($this->appService->getUser()) && $this->isAvailableRouteFor('countdown') && !empty($context)) {
                    if(new DateTime($context['date']) > new DateTime()) {
                        $context['datetime'] = new DateTime($context['date']);
                        $event->setController(function () use ($context) {
                            $response = $this->appService->twig->render('@AequationLabo/security/countdown.html.twig', $context);
                            return new Response($response, 200);
                        });
                    }
                }
            }
        }
    }

    protected function isAvailableRouteFor(
        string $action,
        string $route = null
    ): bool
    {
        $route ??= $this->appService->getRoute();
        switch ($action) {
            case 'countdown':
                // return !in_array($route, ['app_login','app_logout']) && !$this->appService->getUser();
                return !in_array($route, ['app_login','app_logout']) && preg_match('/^app_/', $route);
                break;
            case 'demotest':
                return !in_array($route, ['app_login','app_logout']);
                break;
            default:
                return false;
                break;
        }
    }

    protected function getControllerObjectFromEvent(ControllerEvent $event): mixed
    {
        $controller = $event->getController();
        if (true === is_object($controller)) {
            return (object) $controller;
        }
        if (false === is_array($controller)) {
            return null;
        }
        foreach ($controller as $value) {
            if (true === is_object($value)) {
                return $value;
            }
        }
        return null;
    }

    protected function initAppContext(KernelEvent $event): void
    {
        if(!$this->appService->hasAppContext()) {
            $request = $event->getRequest();
            if($session = $request->hasSession() ? $request->getSession() : null) {
                $this->appService->initializeAppContext($session);
            }
        }
    }

}
