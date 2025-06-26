<?php
namespace Aequation\LaboBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Aequation\LaboBundle\Model\Interface\PdfInterface;
use Aequation\LaboBundle\Model\Interface\PdfizableInterface;
use Aequation\LaboBundle\Model\Interface\WebpageInterface;
// Symfony
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
        PdfizableInterface $pdf,
        string $action
    ): Response
    {
        $response = new Response(status: Response::HTTP_OK);
        $response->headers->set('Content-Type', $pdf->getMime());
        $response->headers->set('Content-Disposition', $action.'; filename="' . $pdf->getFilename(true) . '"');
        if($pdf instanceof PdfInterface && $pdf->getSourcetype() === 2) {
            if($path = $pdf->getFilepathname()) {
                // dd($path, file_exists($path));
                $redir = $this->redirect($path, Response::HTTP_FOUND);
                $redir->headers->set('Content-Type', $pdf->getMime());
                $redir->headers->set('Content-Disposition', $action.'; filename="' . $pdf->getFilename(true) . '"');
                return $redir;
            }
        }
        $response->setContent($this->pdfService->outputDoc($pdf));
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
        $repo = $this->appEm->getRepository(WebpageInterface::class);
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


}