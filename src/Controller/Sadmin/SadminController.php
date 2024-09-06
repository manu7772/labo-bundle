<?php

namespace Aequation\LaboBundle\Controller\Sadmin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/sadmin', name: 'app_sadmin_')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class SadminController extends AbstractController
{
    #[Route('/home', name: 'home')]
    public function index(): Response
    {
        return $this->render('@AequationLabo/sadmin/home/index.html.twig');
    }
}
