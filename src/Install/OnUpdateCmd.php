<?php
namespace Aequation\LaboBundle\Install;

use Aequation\LaboBundle\Install\Interface\LaboInstallInterface;

class OnUpdateCmd implements LaboInstallInterface
{

    public function onUpdate(): static
    {
        print('OK '.__METHOD__.' line '.__LINE__);
        return $this;
    }

}