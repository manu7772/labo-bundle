<?php
namespace Aequation\LaboBundle\Install;

use Aequation\LaboBundle\Install\Interface\LaboInstallInterface;

class OnInstallCmd implements LaboInstallInterface
{

    public function onInstall(): static
    {
        print('OK '.__METHOD__.' line '.__LINE__);
        return $this;
    }

}