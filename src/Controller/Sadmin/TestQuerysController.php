<?php

namespace Aequation\LaboBundle\Controller\Sadmin;

use Aequation\LaboBundle\Service\AppEntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/sadmin', name: 'app_sadmin_')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class TestQuerysController extends AbstractController
{
    #[Route('/test/querys', name: 'test_querys', methods: ['GET','POST'])]
    public function index(
        AppEntityManager $manager,
        Request $request,
    ): Response
    {
        $tested_entity = $request->request->get('tested_entity') ?? $request->query->get('tested_entity');
        $classmetadata = null;
        $classmetadatareport = null;
        if($tested_entity) {
            $classmetadatareport = $manager->getEntityMetadataReport($tested_entity);
            $classmetadata = $classmetadatareport->getClassMetadata();
            $entitiesReports = null;
        } else {
            $entitiesReports = $manager->getEntityMetadataReports();
        }
        $data = [
            'entities' => $manager->getEntityNames(true),
            'entities_reports' => $entitiesReports,
            'entity_service' => $tested_entity ? $manager->getEntityService($tested_entity) : null,
            'request_data' => $request->request->all(),
            'query_data' => $request->query->all(),
            'entity' => $tested_entity,
            'classmetadata' => $classmetadata,
            'classmetadatareport' => $classmetadatareport,
        ];
        return $this->render('@AequationLabo/sadmin/test_querys/index.html.twig', $data);
    }
}
