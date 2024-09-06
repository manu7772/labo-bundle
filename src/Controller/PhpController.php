<?php
namespace Aequation\LaboBundle\Controller;

use Aequation\LaboBundle\Controller\Base\CommonController;
use Aequation\LaboBundle\Service\Interface\LaboBundleServiceInterface;
use Aequation\LaboBundle\Service\Tools\HtmlDom;
use Aequation\LaboBundle\Service\Tools\Strings;
use DOMDocument;
use DOMXPath;
use ReflectionExtension;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/ae-php', name: 'aequation_php_')]
// #[IsGranted('ROLE_ADMIN')]
class PhpController extends CommonController
{

    #[Route(path: '', name: 'home')]
    #[Template('@AequationLabo/php/home.html.twig')]
    public function home(
        LaboBundleServiceInterface $laboService,
    ): array
    {
        $data = [
            'menu' => $laboService->getMenu(),
            'submenu' => $laboService->getSubmenu(),
        ];
        return $data;
    }

    #[Route(path: '/info', name: 'info')]
    #[Template('@AequationLabo/php/info.html.twig')]
    public function info(
        LaboBundleServiceInterface $laboService,
    ): array
    {
        ob_start();
        phpinfo();
        $php_info = ob_get_clean();
        $php_info = HtmlDom::extractFromHtml($php_info, '//body', true);
        $data = [
            'menu' => $laboService->getMenu(),
            'submenu' => $laboService->getSubmenu(),
            'php_info' => $php_info ? $php_info : Strings::markup('<h2 style="color: red; text-align: center; margin: 48px;">PHP INFO GENERATION ERROR</h2>'),
        ];
        return $data;
    }

    #[Route(path: '/extensions', name: 'extensions')]
    #[Template('@AequationLabo/php/extensions.html.twig')]
    public function extensions(
        LaboBundleServiceInterface $laboService,
    ): array
    {
        $extensions = [];
        foreach (get_loaded_extensions() as $extension) {
            $extensions[$extension] = new ReflectionExtension($extension);
        }
        $data = [
            'menu' => $laboService->getMenu(),
            'submenu' => $laboService->getSubmenu(),
            'php_extensions' => $extensions,
        ];
        return $data;
    }

    #[Route(path: '/extension/{extension}', name: 'extension')]
    #[Template('@AequationLabo/php/extension.html.twig')]
    public function extension(
        string $extension,
        LaboBundleServiceInterface $laboService,
    ): array
    {
        $data = [
            'menu' => $laboService->getMenu(),
            'submenu' => $laboService->getSubmenu(),
            'extension' => new ReflectionExtension($extension),
        ];
        return $data;
    }


}