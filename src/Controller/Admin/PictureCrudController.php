<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Entity\Picture;
use Aequation\LaboBundle\Security\Voter\PictureVoter;
// Symfony
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class PictureCrudController extends ImageCrudController
{

    public const ENTITY = Picture::class;
    public const VOTER = PictureVoter::class;

}