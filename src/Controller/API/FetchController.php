<?php

namespace Aequation\LaboBundle\Controller\API;

use Aequation\LaboBundle\Controller\Base\CommonController;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api', name: 'api_')]
class FetchController extends CommonController
{

    #[Route(path: '/darkmode/set/{darkmode<(switch|dark|light)>?switch}', name: 'darkmode_set', methods: ['GET','POST'])]
    public function darkmode(
        AppServiceInterface $appServiceInterface,
        EntityManagerInterface $em,
        Request $request,
        string $darkmode = 'switch',
    ): JsonResponse
    {
        switch ($darkmode) {
            case 'dark':
                $new_darkmode = $appServiceInterface->setDarkmode(true);
                break;
            case 'light':
                $new_darkmode = $appServiceInterface->setDarkmode(false);
            default:
                $new_darkmode = $appServiceInterface->switchDarkmode();
                break;
        }
        return $this->json(
            data: ['darkmode' => $new_darkmode],
            status: JsonResponse::HTTP_OK
        );
    }

}
