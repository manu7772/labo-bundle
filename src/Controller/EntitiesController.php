<?php
namespace Aequation\LaboBundle\Controller;

use Aequation\LaboBundle\Component\ClassmetadataReport;
use Aequation\LaboBundle\Controller\Base\CommonController;
use Aequation\LaboBundle\Repository\Interface\CommonReposInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\AppEntityManager;
use Aequation\LaboBundle\Service\Tools\Encoders;
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
        $data = [
            'entities' => $entities,
            'meta_infos' => $this->getMetaInfos($manager),
            // 'hierarchizeds' => ClassmetadataReport::getHierarchizedReports($metadatas),
        ];
        return $data;
    }

    #[Route(path: '/entity/{classname<[\w\d\\\]+>}', name: 'entity_show')]
    #[Template('@AequationLabo/labo/entity_show.html.twig')]
    public function entity_show(
        string $classname,
        AppEntityManagerInterface $manager,
    ): array
    {
        $data = [
            'classname' => $classname,
            'meta_info' => $manager->getEntityMetadataReport($classname),
            'meta_infos' => $this->getMetaInfos($manager),
        ];
        return $data;
    }

    #[Route(path: '/entity/{euid}/{context}', name: 'entity_detail', requirements: ['euid' => Encoders::EUID_SCHEMA], defaults: ['context' => null])]
    #[Template('@AequationLabo/labo/entity_detail.html.twig')]
    public function entity_detail(
        AppEntityManagerInterface $manager,
        string $euid,
        ?string $context = null,
    ): array
    {
        if(empty($context)) {
            $context = ['groups' => ['index']];
        } else {
            $context = json_decode($context, true);
        }
        $entity = Encoders::isEuidFormatValid($euid) ? $manager->findEntityByEuid($euid) : null;
        $previous = $next = null;
        if ($entity) {
            $repo = $manager->getRepository($entity->getClassname());
            if($repo instanceof CommonReposInterface) {
                $list = $repo->findAllEuids();
                $found = false;
                foreach ($list as $euid) {
                    if($found) {
                        $next = $euid;
                        break;
                    }
                    if($euid === $entity->getEuid()) {
                        $found = true;
                    } else {
                        $previous = $euid;
                    }
                }
            }
        }
        $data = [
            'euid' => $euid,
            'previous' => $previous,
            'next' => $next,
            'entity' => $entity,
            'context' => $context,
            'classname' => $entity ? $entity->getClassname() : null,
            'meta_info' => $entity ? $manager->getEntityMetadataReport($entity->getClassname()) : null,
            // 'meta_infos' => $this->getMetaInfos($manager),
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

    protected function getMetaInfos(AppEntityManagerInterface $manager): array
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
            // $this->addFlash('info', vsprintf('Toutes les entités semblent valides.', []));
        }
        ClassmetadataReport::sortReports($metadatas);
        return $metadatas;
    }


}