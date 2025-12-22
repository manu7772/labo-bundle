<?php
namespace Aequation\LaboBundle\Security\Voter;

use Aequation\LaboBundle\Model\Interface\PhotoInterface;

class PhotoVoter extends ImageVoter
{

    public const INTERFACE = PhotoInterface::class;

}
