<?php

namespace Aequation\LaboBundle\Controller\Sadmin;

use Aequation\LaboBundle\Service\AppService;
use Aequation\LaboBundle\Service\Tools\Files;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/sadmin', name: 'app_sadmin_')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class ContextController extends AbstractController
{
    #[Route('/context', name: 'context')]
    public function index(
        AppService $appService,
        KernelInterface $kernel,
    ): Response
    {

        /** @var array $data */
        $data = [
            'tests' => [
                // 'parameters' => [
                //     'orm.mappings' => $this->getParameter('orm.mappings'),
                // ],
                'project' => [
                    'project_dir' => $appService->getProjectDir(endSeparator: false),
                    'src_dir' => $appService->getDir(path: 'src', endSeparator: false),
                    'config_dir' => $appService->getDir(path: 'config', endSeparator: false),
                    // 'not_found_dir' => $appService->getDir(path: 'not_found', endSeparator: false),
                    'dirs' => Files::listDirs(),
                    'files' => Files::listFiles(),
                    'EOL' => PHP_EOL,
                    'DIR_SEPARATOR' => DIRECTORY_SEPARATOR,
                ],
                'firewalls' => [
                    'current' => $appService->getFirewallName(),
                    'list' => $appService->getFirewalls(),
                ],
                'AppService' => $appService,
                'kernel' => $kernel,
            ],
        ];

        return $this->render('@AequationLabo/sadmin/context/index.html.twig', $data);
    }
}
