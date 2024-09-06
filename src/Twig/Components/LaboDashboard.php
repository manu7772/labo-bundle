<?php
namespace Aequation\LaboBundle\Twig\Components;

use Aequation\LaboBundle\Component\ClassmetadataReport;
use Aequation\LaboBundle\Form\Type\WebpageType;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use Exception;

#[AsLiveComponent]
final class LaboDashboard extends AbstractController
{
    use DefaultActionTrait;

    public ?string $title = null;

}
