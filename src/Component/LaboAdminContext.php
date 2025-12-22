<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Component\Interface\LaboAdminContextInterface;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
// Symfony
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
// PHP
use ReflectionClass;

class LaboAdminContext implements LaboAdminContextInterface
{

    public readonly bool $isInstantiable;

    public function __construct(
        public readonly AbstractCrudController $controller,
        public readonly AdminContext $context
    )
    {
        // dump($this->context);
        $RC = new ReflectionClass($this->context->getEntity()->getFqcn());
        $this->isInstantiable = $RC->isInstantiable();
    }

    public function getContext(): AdminContext
    {
        return $this->context;
    }

    public function __get($name)
    {
        return $this->context->$name;
    }

    public function __call($name, $arguments)
    {
        return $this->context->$name(...$arguments);
    }

    public function __isset($name)
    {
        return isset($this->context->$name);
    }

    public function __set($name, $value)
    {
        $this->context->$name = $value;
    }

    public function getInstance(): ?object
    {
        return $this->context->getEntity()->getInstance() ?: null;
    }

    public function getInstanceOrClass(): string|object
    {
        return $this->context->getEntity()->getInstance() ?: $this->context->getEntity()->getFqcn();
    }

    public function isInstantiable(): bool
    {
        return $this->isInstantiable;
    }

    public function getTimezone(): string
    {
        $user = $this->getUser();
        if($user instanceof LaboUserInterface) {
            return $user->getTimezone();
        }
        return $this->controller->getParameter('timezone');
    }

}