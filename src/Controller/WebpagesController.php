<?php
namespace Aequation\LaboBundle\Controller;

use Aequation\LaboBundle\Component\WebpageContainer;
use Aequation\LaboBundle\Model\Final\FinalWebpageInterface;
use Aequation\LaboBundle\Repository\Interface\WebpageRepositoryInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// #[Route(name: 'app_')]
class WebpagesController extends AbstractController
{

    public function __construct(
        protected AppEntityManagerInterface $appEm,
    ) {
    }

    // #[Route('', name: 'home')]
    public function index(): Response
    {
        $wpContainer = new WebpageContainer([], $this->appEm);
        return $wpContainer->isError()
            ? $this->redirectToRoute('app_error')
            : $this->render($wpContainer->getTwigfile(), $wpContainer->toArray());
    }

    // #[Route('/page/{path<.+>?null}', name: 'webpage', defaults: ['path' => null])]
    public function webpage(
        // #[MapEntity(mapping:['webpage' => 'slug'])]
        ?string $path
    ): Response
    {
        $wpContainer = new WebpageContainer(explode('/', $path ?? ''), $this->appEm);
        switch (true) {
            case $wpContainer->isHome():
                return $this->redirectToRoute('app_home');
                break;
            case $wpContainer->isWebpage() || $wpContainer->isMenu():
                return $this->render($wpContainer->getTwigfile(), $wpContainer->toArray(true));
                break;
            // case $wpContainer->isError():
            //     return $this->redirectToRoute('app_error');
            //     break;
            default:
                return $this->redirectToRoute('app_error');
                break;
        }
    }

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
