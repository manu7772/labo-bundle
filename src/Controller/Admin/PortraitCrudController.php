<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Entity\Portrait;
use Aequation\LaboBundle\Security\Voter\PortraitVoter;
// Symfony
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class PortraitCrudController extends ImageCrudController
{

    public const ENTITY = Portrait::class;
    public const VOTER = PortraitVoter::class;

}