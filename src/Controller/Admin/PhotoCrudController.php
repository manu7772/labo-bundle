<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Entity\Photo;
use Aequation\LaboBundle\Security\Voter\PhotoVoter;
// Symfony
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class PhotoCrudController extends ImageCrudController
{

    public const ENTITY = Photo::class;
    public const VOTER = PhotoVoter::class;

}