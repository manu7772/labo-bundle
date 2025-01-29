<?php
namespace Aequation\LaboBundle\Repository;

use Aequation\LaboBundle\Entity\LaboArticle;
use Aequation\LaboBundle\Repository\Base\CommonRepos;
use Aequation\LaboBundle\Repository\Interface\LaboArticleRepositoryInterface;

class LaboArticleRepository extends CommonRepos implements LaboArticleRepositoryInterface
{
    
    const ENTITY_CLASS = LaboArticle::class;
    const NAME = 'laboarticle';

}