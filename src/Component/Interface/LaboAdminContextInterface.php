<?php
namespace Aequation\LaboBundle\Component\Interface;

use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
// Symfony
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

interface LaboAdminContextInterface
{

    public function __construct(
        AbstractCrudController $controller,
        AdminContext $context
    );

    public function getInstance(): ?object;
    public function getInstanceOrClass(): string|object;
    public function isInstantiable(): bool;
    public function getTimezone(): string;
    public function getContext(): AdminContext;

}