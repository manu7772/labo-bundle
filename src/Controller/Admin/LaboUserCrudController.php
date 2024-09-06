<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Entity\LaboUser;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLABORATOR')]
class LaboUserCrudController extends AbstractCrudController
{

    public const ENTITY = LaboUser::class;

    public static function getEntityFqcn(): string
    {
        return static::ENTITY;
    }

}