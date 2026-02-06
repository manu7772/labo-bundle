<?php
namespace Aequation\LaboBundle\Security\Voter;

use Aequation\LaboBundle\Model\Interface\SlidebaseInterface;
use Aequation\LaboBundle\Security\Voter\Base\BaseVoter;
use Aequation\LaboBundle\Service\Interface\SliderServiceInterface;
use Aequation\LaboBundle\Service\Tools\HttpRequest;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SliderbaseVoter extends BaseVoter
{

    public const INTERFACE = SlidebaseInterface::class;


}