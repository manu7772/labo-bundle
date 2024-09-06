<?php
namespace Aequation\LaboBundle\Service\Interface;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;

interface FormServiceInterface extends ServiceInterface
{

    public function getForm(string $type = FormType::class, mixed $data = null, array $options = []): FormInterface;

}