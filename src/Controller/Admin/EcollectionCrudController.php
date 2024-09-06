<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;
use Aequation\LaboBundle\Entity\Ecollection;

use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLABORATOR')]
class EcollectionCrudController extends BaseCrudController
{

    public const ENTITY = Ecollection::class;

}