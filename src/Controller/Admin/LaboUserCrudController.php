<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;
use Aequation\LaboBundle\Entity\LaboUser;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLABORATOR')]
abstract class LaboUserCrudController extends BaseCrudController
{

    public const ENTITY = LaboUser::class;

}