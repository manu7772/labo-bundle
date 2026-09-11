<?php
namespace Aequation\LaboBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SeoController extends AbstractController
{

    #[Route('/{robots}.txt', name: 'robots_txt', methods: ['GET'])]
    protected function robotsTxt(): Response
    {
        $response = new Response(status: Response::HTTP_OK);
        $response->headers->set('Content-Type', 'text/plain');
        $txt = file_get_contents('robots.txt');
        $response->setContent($txt);
        return $response;
    }

}