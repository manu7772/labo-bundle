<?php
namespace Aequation\LaboBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Aequation\LaboBundle\Model\Interface\PdfInterface;
use Aequation\LaboBundle\Model\Interface\PdfizableInterface;
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
        $response->headers->set('Content-Disposition', $action.'; filename="' . $pdf->getFilename() . '"');
        if($pdf instanceof PdfInterface && $pdf->getSourcetype() === 2) {
            if($path = $pdf->getFilepathname()) {
                dd($path, file_exists($path));
                return $this->redirect($path, Response::HTTP_FOUND);
            }
        }
        $response->setContent($this->pdfService->outputDoc($pdf));
        return $response;
    }

    #[Route('/pdf/{action<(inline|attachment)>}/{pdf}/{paper}/{orientation}', name: 'pdf_action', methods: ['GET'], defaults: ['action' => 'inline', 'paper' => null, 'orientation' => 'portrait'])]
    public function pdfOutputAction(
        string $action = 'inline',
        string $pdf,
        string $paper = null,
        string $orientation = 'portrait',
        PdfServiceInterface $pdfService
    ): Response
    {
        $doc = $this->appEm->findEntityByUniqueValue($pdf);
        /** @var ServiceEntityRepository $repo */
        $repo = $this->pdfService->getRepository();
        $doc ??= $repo->find($pdf);
        $doc ??= $repo->findOneBy(['slug' => $pdf]);
        if($doc instanceof PdfizableInterface) {
            return $this->getOutputResponse($doc, $action);
        }
        throw $this->createNotFoundException(vsprintf('Le document %s n\'existe pas', [$pdf]));
    }


}