<?php
namespace Aequation\LaboBundle\Service\Interface;

use Aequation\LaboBundle\Component\Jelastic;
use Symfony\Component\Form\FormInterface;

interface LaboBundleServiceInterface extends AppServiceInterface
{

    public function getMenu(): array;
    public function getSubmenu(): array;
    public function getEntitymenu(): array;
    public function getJelasticForm(Jelastic $data = null, array $options = []): FormInterface;
    public function getJelasticFile(?array $data = null): ?string;

}