<?php
namespace Aequation\LaboBundle\Controller;

use Aequation\LaboBundle\Component\ClassmetadataReport;
use Aequation\LaboBundle\Controller\Base\CommonController;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\AppEntityManager;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/ae-labo', name: 'aequation_labo_')]
// #[IsGranted('ROLE_ADMIN')]
class EntitiesController extends CommonController
{

    #[Route(path: '/entity', name: 'entity_list')]
    #[Template('@AequationLabo/labo/entity_list.html.twig')]
    public function entity_list(
        AppEntityManagerInterface $manager,
    ): array
    {
        $entities = $manager->getEntityNames(false, false);
        $fails = [];
        foreach ($entities as $classname) {
            $metadatas[$classname] = $manager->getEntityMetadataReport($classname);
            if($metadatas[$classname]->hasErrors()) {
                $fails[$classname] = $metadatas[$classname];
            }
        }
        if(count($fails) > 0) {
            $this->addFlash('error', vsprintf('Des erreurs (total : %d) ont été trouvées dans les structures d\'entités. Veuillez les corriger svp.', [count($fails)]));
        } else {
            $this->addFlash('info', vsprintf('Toutes les entités semblent valides.', []));
        }
        ClassmetadataReport::sortReports($metadatas);
        $data = [
            'entities' => $entities,
            'meta_infos' => $metadatas,
            // 'hierarchizeds' => ClassmetadataReport::getHierarchizedReports($metadatas),
        ];
        return $data;
    }

    #[Route(path: '/entity/{classname}', name: 'entity_show')]
    #[Template('@AequationLabo/labo/entity_show.html.twig')]
    public function entity_show(
        string $classname,
        AppEntityManagerInterface $manager,
    ): array
    {
        $data = [
            'classname' => $classname,
            'meta_info' => $manager->getEntityMetadataReport($classname),
        ];
        return $data;
    }

    #[Route(path: '/crudvoters', name: 'crudvoters')]
    #[Template('@AequationLabo/labo/crudvoters.html.twig')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function crudvoters(
        AppEntityManagerInterface $manager,
    ): array
    {
        $entities = $manager->getEntityNames(false, false);
        $data = [
            'entities' => $entities,
        ];
        return $data;
    }

    #[Route(path: '/crudvoters/{class}', name: 'crudvoter_class')]
    #[Template('@AequationLabo/labo/crudvoter.html.twig')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function crudvoterClass(
        string $class,
        AppEntityManagerInterface $manager,
    ): array
    {
        /** @var ClassmetadataReport */
        $metadata = $manager->getEntityMetadataReport($class);
        $errors = $metadata->getErrors();
        if($errors) {
            $this->addFlash('error', vsprintf('Cette entité "%s" contient des erreurs !<br>%s<br><a href="%s">Voir les détails</a>', [$metadata->name, '<ul><li>'.implode('</li><li>', $errors).'</li></ul>', $this->generateUrl('aequation_labo_entities')]));
        }
        $data = [
            'class' => $class,
            'metadata' => $metadata,
        ];
        return $data;
    }


}