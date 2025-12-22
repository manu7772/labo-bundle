<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Entity\Pdf;
use Aequation\LaboBundle\Model\Interface\PdfInterface;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Service\Interface\PdfServiceInterface;
use Aequation\LaboBundle\Model\Final\FinalWebpageInterface;
use Aequation\LaboBundle\Model\Interface\PdfizableInterface;
// Symfony
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Nucleos\DompdfBundle\Factory\DompdfFactoryInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
// PHP
use DateTimeImmutable;

#[AsAlias(PdfServiceInterface::class, public: true)]
class PdfService extends ItemService implements PdfServiceInterface
{
    public const ENTITY = Pdf::class;

    public function __construct(
        protected EntityManagerInterface $em,
        protected AppServiceInterface $appService,
        protected AccessDecisionManagerInterface $accessDecisionManager,
        protected ValidatorInterface $validator,
        protected UploaderHelper $vichHelper,
        protected CacheManager $liipCache,
        protected DompdfFactoryInterface $dompdfFactory,
    )
    {
        parent::__construct($em, $appService, $accessDecisionManager, $validator);
    }

    /**
     * Output a PDF from HTML content
     * 
     * @param string $htmlContent
     * @param string $paper
     * @param string $orientation
     * @param array $options
     * @return string
     */
    public function outputHtml(
        string $htmlContent,
        string $paper = 'A4',
        string $orientation = 'portrait',
        array $options = []
    ): string
    {
        $dompdf = $this->dompdfFactory->create($options);
        // --- Set options
        // $options = $dompdf->getOptions();
        // $options->setChroot(['/public/assets', '/public/media']);
        // $options->set('isRemoteEnabled', true);
        // dd($options);
        // $dompdf->setOptions($options);
        // --- Load HTML content
        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();
        return $dompdf->output();
    }

    /**
     * Get the browser path of a PDF
     * 
     * @param PdfInterface $pdf
     * @return string
     */
    public function getBrowserPath(
        PdfInterface $pdf,
    ): string
    {
        $browserPath = $this->vichHelper->asset($pdf);
        $browserPath = preg_replace('/((\.pdf)+)$/i', '.pdf', $browserPath);
        return $browserPath;
    }

    /**
     * Output a PDF from a PdfizableInterface
     * 
     * @param PdfizableInterface $pdf
     * @return string
     */
    public function outputDoc(
        PdfizableInterface $pdf,
        string $template = '@AequationLabo/pdf/webpage_export.html.twig'
    ): string
    {
        $content = $pdf->getContent();
        if($pdf instanceof FinalWebpageInterface) {
            $htmlContent = $this->appService->twig->render($template, ['webpage' => $pdf, 'date' => new DateTimeImmutable(), 'appServie' => $this->appService]);
            // if (!empty($photo = $pdf->getPhoto())) {
            //     $content = '<img src="'.$photo->getFilepathname().'" style="width: 80%; height: auto; margin: 20px auto;">'.$content;
            // }
            // $content = '<h1 style="font-size: 2.4rem; text-align: center; margin: 0 auto 48px; color: darkblue;">'.$pdf->getTitle().'</h1>'.$content;
        } else {
            $template = $this->appService->twig->createTemplate($content, $pdf->getFilename(true));
            $htmlContent = $template->render(['date' => new DateTimeImmutable(), 'appServie' => $this->appService]);
        }
        return $this->outputHtml($htmlContent, $pdf->getPaper(), $pdf->getOrientation());
    }


}