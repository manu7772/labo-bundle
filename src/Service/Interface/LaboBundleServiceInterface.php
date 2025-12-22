<?php
namespace Aequation\LaboBundle\Service\Interface;

use Aequation\LaboBundle\Component\Jelastic;
use Symfony\Component\Form\FormInterface;

interface LaboBundleServiceInterface extends AppServiceInterface
{

    public const APP_CACHENAME_SERVICES_LIST = 'app_services_list';
    public const APP_CACHENAME_SERVICES_LIFE = null;
    public const SOURCES_PHP = ['src','vendor/aequation/labo'];


    public function getAppServices(): array;
    public function getServices(): array;

    public function getJelasticForm(?Jelastic $data = null, array $options = []): FormInterface;
    public function createJelastic(): Jelastic;
    public function getJelasticFile(?array $data = null): ?string;
    public function getJelasticModel(): string|false;
    
    public function getMenu(): array;
    public function getSubmenu(): array;
    public function getEntitymenu(): array;

    public function getDeclaredClasses(null|array|object|string $listOfClasses = null): array;
    public function getAppAttributesList(null|array|object|string $listOfClasses = null): array;

}