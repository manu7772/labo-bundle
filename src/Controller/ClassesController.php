<?php
namespace Aequation\LaboBundle\Controller;

use Aequation\LaboBundle\Component\ClassmetadataReport;
use Aequation\LaboBundle\Controller\Base\CommonController;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\AppEntityManager;
use Aequation\LaboBundle\Service\Interface\LaboBundleServiceInterface;
use Aequation\LaboBundle\Service\Tools\Classes;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/ae-labo', name: 'aequation_labo_')]
// #[IsGranted('ROLE_ADMIN')]
class ClassesController extends CommonController
{

    #[Route(path: '/classes', name: 'class_list')]
    #[Template('@AequationLabo/labo/classe_list.html.twig')]
    public function class_list(
        LaboBundleServiceInterface $manager
    ): array
    {
        $classes = Classes::REGEX_APP_CLASS;
        Classes::filterDeclaredClasses($classes, true);
        $data = [
            'classes' => $classes,
        ];
        return $data;
    }

    #[Route(path: '/classes/{classname}', name: 'class_show')]
    #[Template('@AequationLabo/labo/classe_show.html.twig')]
    public function class_show(
        string $classname,
        LaboBundleServiceInterface $manager,
    ): array
    {
        $data = [
            'classname' => $classname,
            'attributes' => $manager->getAppAttributesList($classname),
        ];
        return $data;
    }

}