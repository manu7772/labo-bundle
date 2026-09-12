<?php
namespace Aequation\LaboBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// #[Route('/seo')]
class SeoController extends AbstractController
{

    /**
     * Get content of xml file if exists
     * 
     * @param string $file
     * @return Response
     */
    #[Route('/{file}.xml', name: 'xml_file', methods: ['GET'], priority: -1)]
    public function xmlFile(
        string $file,
    ): Response
    {
        $response = new Response(status: Response::HTTP_OK);
        $response->headers->set('Content-Type', 'application/xml');
        if($xml = @file_get_contents(vsprintf('%s.%s', [$file, 'xml']))) {
            $response->setContent($xml);
        } else {
            // Generate 404
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
            $response->setContent(null);
        }
        return $response;
    }


    /**
     * Get content of txt file if exists
     * 
     * @param string $file
     * @return Response
     */
    #[Route('/{file}.txt', name: 'txt_file', methods: ['GET'], priority: -1)]
    public function fileTxt(
        string $file,
    ): Response
    {
        $response = new Response(status: Response::HTTP_OK);
        $response->headers->set('Content-Type', 'text/plain');
        if($txt = @file_get_contents(vsprintf('%s.%s', [$file, 'txt']))) {
            $response->setContent($txt);
        } else {
            // Generate 404
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
            $response->setContent(null);
        }
        return $response;
    }

}