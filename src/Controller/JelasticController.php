<?php
namespace Aequation\LaboBundle\Controller;

use Aequation\LaboBundle\Controller\Base\CommonController;
use Aequation\LaboBundle\Service\Interface\LaboBundleServiceInterface;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Twig\Attribute\Template;

#[Route(path: '/ae-jelastic', name: 'aequation_jelastic_')]
// #[IsGranted('ROLE_SUPER_ADMIN')]
class JelasticController extends CommonController
{

    #[Route(path: '', name: 'home')]
    #[Template('@AequationLabo/jelastic/home.html.twig')]
    public function home(
        LaboBundleServiceInterface $laboService,
    ): array
    {
        $data = [];
        return $data;
    }

}