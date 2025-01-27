<?php
namespace Aequation\LaboBundle\Model\Final;

use Aequation\LaboBundle\Model\Interface\LaboRelinkInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

interface FinalUrlinkInterface extends LaboRelinkInterface
{
    public function setUrl(?string $url): static;
    public function getUrl(?int $referenceTypeIfRoute = Router::ABSOLUTE_PATH): string;
    public function setRoute(?string $route): static;
    public function getRoute(): string;

}