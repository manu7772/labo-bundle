<?php
namespace Aequation\LaboBundle\Controller;

use Aequation\LaboBundle\Component\WebpageContainer;
use Aequation\LaboBundle\Model\Final\FinalMenuInterface;
use Aequation\LaboBundle\Model\Final\FinalWebpageInterface;
use Aequation\LaboBundle\Repository\Interface\MenuRepositoryInterface;
use Aequation\LaboBundle\Repository\Interface\WebpageRepositoryInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\LaboWebpageServiceInterface;
// Symfony
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

// #[Route(name: 'app_')]
class WebpagesController extends AbstractController
{

    public function __construct(
        protected AppEntityManagerInterface $appEm,
        // protected WebpageRepositoryInterface $webpageRepository,
        // protected MenuRepositoryInterface $menuRepository,
    ) {
    }

    // #[Route('', name: 'home')]
    public function index(
        LaboWebpageServiceInterface $webpageService
    ): Response
    {
        // /** @var ?FinalWebpageInterface */
        // $webpage = $webpageService->getPreferedWebpage();
        $wpContainer = new WebpageContainer([], $this->appEm);
        return $this->render($wpContainer->getTwigfile(), $wpContainer->toArray());
        // return $webpage instanceof FinalWebpageInterface
        //     ? $this->render($webpage->getTwigfile(), ["webpage" => $webpage])
        //     : $this->render('webpage/homepage.html.twig');
    }

    // #[Route('/page/{webpage<[a-z0-9-]+>}/{subwebpage<[a-z0-9-]+>?null}', name: 'webpage', defaults: ['subwebpage' => null])]
    public function webpage(
        // #[MapEntity(mapping:['webpage' => 'slug'])]
        string $webpage,
        ?string $subwebpage = null
    ): Response
    {
        $wpContainer = new WebpageContainer([$webpage, $subwebpage], $this->appEm);
        switch (true) {
            case $wpContainer->isHome():
                return $this->redirectToRoute('app_home');
                break;
            case $wpContainer->isWebpage() || $wpContainer->isMenu():
                return $this->render($wpContainer->getTwigfile(), $wpContainer->toArray());
                break;
            // case $wpContainer->isDirectionError():
            //     return $this->redirectToRoute('app_error');
            //     break;
            default:
                return $this->redirectToRoute('app_error');
                break;
        }

        // $slug = $webpage;
        // if(empty($subwebpage) && ($webpage = $this->webpageRepository->findOneBy(['slug' => $slug])) && $webpage->isActive()) {
        //     return $webpage->isPrefered()
        //         ? $this->redirectToRoute('app_home')
        //         : $this->render($webpage->getTwigfile(), [
        //             "webpage" => $webpage,
        //             "subwebpage" => null,
        //             "menu" => null,
        //             // "child_webpage" => null,
        //         ]);
        // } else if(($menu = $this->menuRepository->findOneBy(['slug' => $slug])) && $menu->isActive()) {
        //     /** @var FinalMenuInterface $menu */
        //     $menuItems = $menu->getItems()->filter(fn($item) => $item->isActive());
        //     if($subwebpage) {
        //         $itemslug = $subwebpage;
        //         if(($item = $this->webpageRepository->findOneBy(['slug' => $itemslug])) && $item->isActive() && $menuItems->contains($item)) {
        //             $subwebpage = $item;
        //         }
        //     }
        //     if(!is_object($subwebpage)) {
        //         $subwebpage = $menuItems->first() ?: null;
        //     }
        //     /** @var ?FinalWebpageInterface $menuwebpage */
        //     if($menuwebpage = $menu->getWebpage()) {
        //         $menuwebpage->currentItem = $menu;
        //     }
        //     return $this->render($menuwebpage?->getTwigfile() ?: 'menu/menu_items.html.twig', [
        //         "webpage" => $menuwebpage,
        //         "subwebpage" => $subwebpage ?? null,
        //         "menu" => $menu,
        //         // "child_webpage" => $webpage,
        //     ]);
        // }
        // // throw new Exception('Webpage not found');
        // $this->addFlash('warning', new TranslatableMessage('Cette page est introuvable'));
        // return $this->redirectToRoute('app_error');
    }

    // #[Route('/menu/{menu<[a-z0-9-]+>}/{webpage<[a-z0-9-]+>?null}', name: 'menu')]
    // public function menu(
    //     #[MapEntity(mapping:['menu' => 'slug'])]
    //     ?Menu $menu,
    //     #[MapEntity(mapping:['webpage' => 'slug'])]
    //     ?Webpage $webpage,
    // ): Response
    // {
    //     if(empty($menu) || !$menu->isActive()) {
    //         // throw new Exception('Menu not found');
    //         $this->addFlash('warning', new TranslatableMessage('Cette page est introuvable'));
    //         return $this->redirectToRoute('app_error');
    //     }
    //     /** @var ?FinalWebpageInterface */
    //     if($menuwebpage = $menu->getWebpage()) {
    //         $menuwebpage->currentItem = $menu;
    //     }
    //     return $this->render($menuwebpage?->getTwigfile() ?: 'menu/menu_items.html.twig', [
    //         "webpage" => $menuwebpage,
    //         "menu" => $menu,
    //         "child_webpage" => $webpage,
    //     ]);
    // }

    // #[Route('/error', name: 'error')]
    public function error(
        WebpageRepositoryInterface $webpageRepository
    ): Response
    {
        $webpage = $webpageRepository->findOneBy(['name' => 'Page not found']);
        $reponse = new Response();
        $reponse->setStatusCode(Response::HTTP_NOT_FOUND);
        return $webpage instanceof FinalWebpageInterface
            ? $this->render($webpage->getTwigfile(), ["webpage" => $webpage], $reponse)
            : $this->render('webpage/error.html.twig', [], $reponse);
    }


}
