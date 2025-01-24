<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Entity\LaboArticle;
use Aequation\LaboBundle\Service\Interface\LaboArticleServiceInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(LaboArticleServiceInterface::class, public: true)]
class LaboArticleService extends ItemService implements LaboArticleServiceInterface
{
    
    public const ENTITY = LaboArticle::class;

}