<?php
namespace Aequation\LaboBundle\Controller;

use Aequation\LaboBundle\Component\FinderLabo;
use Aequation\LaboBundle\Component\SplFileLabo;
use Aequation\LaboBundle\Controller\Base\CommonController;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Service\Interface\CacheServiceInterface;
use Aequation\LaboBundle\Service\Interface\LaboBundleServiceInterface;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;

#[Route(path: '/ae-cache', name: 'aequation_cache_')]
class CachesController extends CommonController
{

    #[Route(path: '', name: 'home')]
    #[Template('@AequationLabo/caches/home.html.twig')]
    public function home(
        LaboBundleServiceInterface $laboService,
        AppServiceInterface $appService,
        CacheServiceInterface $cacheService
    ): array
    {
        $data = [
            'cacheService' => $cacheService,
            'finder' => new SplFileLabo($appService->getCache()->getCacheDir()),
        ];
        return $data;
    }

    #[Route(path: '/home-success/{message}', name: 'home_success')]
    public function homeSuccess(
        ?string $message = null
    ): RedirectResponse
    {
        $this->addFlash('success', empty(trim($message)) ? 'L\'opération est terminée' : $message);
        return $this->redirectToRoute('aequation_cache_home');
    }

    #[Route(path: '/clear/{name}', name: 'clear', priority: 2)]
    #[Template('@AequationLabo/caches/home.html.twig')]
    public function clearNamedCache(
        CacheServiceInterface $cacheService,
        string $name = null,
    ): RedirectResponse
    {
        if(empty($name)) {
            $cacheService->deleteAll();
            $this->addFlash('success', "Toutes les caches ont été vidées !");
        } else {
            $cacheService->delete($name);
            $this->addFlash('success', "La cache $name a été vidée !");
        }
        return $this->redirectToRoute('aequation_cache_home');
    }

    #[Route(path: '/toggle-dev-shortcut/{name}', name: 'toggle_dev_shortcut')]
    #[Template('@AequationLabo/caches/home.html.twig')]
    public function toggleDevShortcutCache(
        CacheServiceInterface $cacheService,
        string $name
    ): RedirectResponse
    {
        switch ($name) {
            case '#all-on':
                $cacheService->setDevShortcutAll(true);
                $this->addFlash('info', "Les caches ont été désactivées.<br>Le site sera un peu plus lent.");
                break;
                case '#all-off':
                    $cacheService->setDevShortcutAll(false);
                    $this->addFlash('info', "Les caches ont été activées.<br>Le site sera un peu plus rapide.");
                break;
            default:
                $cacheService->toggleDevShortcut($name);
                break;
        }
        return $this->redirectToRoute('aequation_cache_home');
    }

    #[Route(path: '/delete/{method<\w+>?exec}', name: 'delete_get', methods: ['GET'])]
    #[Template('@AequationLabo/caches/home.html.twig')]
    public function deleteGetCache(
        CacheServiceInterface $cacheService,
        string $method = 'exec',
    ): RedirectResponse
    {
        $cacheService->cacheClear($method);
        return $this->redirectToRoute('aequation_cache_home_success', ['message' => 'La cache a été supprimée']);
    }

    #[Route(path: '/delete/{method<\w+>?exec}', name: 'delete_post', methods: ['POST'])]
    public function deletePostCache(
        CacheServiceInterface $cacheService,
        string $method = 'exec',
    ): JsonResponse
    {
        try {
            $cacheService->cacheClear($method);
            $result = 200;
        } catch (\Throwable $th) {
            //throw $th;
            $result = 500;
        }
        return new JsonResponse($result);
    }

}