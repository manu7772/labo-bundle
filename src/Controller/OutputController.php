<?php
namespace Aequation\LaboBundle\Controller;

// Aequation
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Aequation\LaboBundle\Model\Interface\PdfInterface;
use Aequation\LaboBundle\Model\Final\FinalWebpageInterface;
use Aequation\LaboBundle\Model\Interface\PdfizableInterface;
// Symfony
use Aequation\LaboBundle\Model\Final\FinalVideolinkInterface;
use Aequation\LaboBundle\Service\Interface\PdfServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

#[Route('/output', name: 'output_')]
class OutputController extends AbstractController
{

    public function __construct(
        protected AppEntityManagerInterface $appEm,
        protected PdfServiceInterface $pdfService
    ) {}

    protected function getOutputResponse(
        object $doc,
        string $action
    ): Response
    {
        $response = new Response(status: Response::HTTP_OK);
        $response->headers->set('Content-Type', $doc->getMime());
        $response->headers->set('Content-Disposition', $action.'; filename="' . $doc->getFilename(true) . '"');
        switch(true) {
            case $doc instanceof FinalVideolinkInterface:
                // VIDEO
                if($path = $doc->getFilepathname()) {
                    $redir = $this->redirect($path, Response::HTTP_FOUND);
                    $redir->headers->set('Content-Type', $doc->getMime());
                    $redir->headers->set('Content-Disposition', $action.'; filename="' . $doc->getFilename(true) . '"');
                    return $redir;
                }
                break;
            case $doc instanceof PdfInterface && $doc->getSourcetype() === 2:
                // PDF
                if($path = $doc->getFilepathname()) {
                    $redir = $this->redirect($path, Response::HTTP_FOUND);
                    $redir->headers->set('Content-Type', $doc->getMime());
                    $redir->headers->set('Content-Disposition', $action.'; filename="' . $doc->getFilename(true) . '"');
                    return $redir;
                }
                break;
        }
        $response->setContent($this->pdfService->outputDoc($doc));
        return $response;
    }

    /**
     * ACTION : inline / attachment
     */
    #[Route('/pdf/{action<(inline|attachment)>}/{pdf}/{paper}/{orientation}', name: 'pdf_action', methods: ['GET'], defaults: ['action' => 'inline', 'paper' => null, 'orientation' => 'portrait'])]
    public function pdfOutputAction(
        PdfServiceInterface $pdfService,
        string $pdf,
        string $action = 'inline',
        ?string $paper = null,
        string $orientation = 'portrait'
    ): Response
    {
        // Try by Webpage slug first
        /** @var ServiceEntityRepository */
        $repo = $this->appEm->getRepository(FinalWebpageInterface::class);
        $doc = $repo->findOneBySlug($pdf) ?? $this->appEm->findEntityByUniqueValue($pdf);
        if(!$doc) {
            // Try find Pdf
            /** @var ServiceEntityRepository $repo */
            $repo = $this->pdfService->getRepository();
            $doc = $repo->find($pdf) ?? $repo->findOneBy(['slug' => $pdf]);
        }
        if($doc instanceof PdfizableInterface) {
            return $this->getOutputResponse($doc, $action);
        }
        throw $this->createNotFoundException(vsprintf('Le document %s n\'existe pas', [$pdf]));
    }

    #[Route('/video/{action<(inline|attachment)>}/{video}', name: 'video_action', methods: ['GET'], defaults: ['action' => 'inline'])]
    public function videoOutputAction(
        string $video,
        string $action = 'inline'
    ): Response
    {
        // TODO: implement video output
        // throw $this->createNotFoundException('Not implemented yet.');
        /** @var ServiceEntityRepository */
        $repo = $this->appEm->getRepository(FinalVideolinkInterface::class);
        $video = $repo->findOneBySlug($video) ?? $this->appEm->findEntityByUniqueValue($video);
        if($video) {
            return $this->getOutputResponse($video, $action);
        }
        throw $this->createNotFoundException(vsprintf('La vidéo %s n\'existe pas', [$video]));
    }


}