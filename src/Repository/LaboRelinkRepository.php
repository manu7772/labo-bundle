<?php
namespace Aequation\LaboBundle\Repository;

use Aequation\LaboBundle\Entity\LaboRelink;
use Aequation\LaboBundle\Repository\Base\CommonRepos;
use Aequation\LaboBundle\Repository\Interface\LaboRelinkRepositoryInterface;

class LaboRelinkRepository extends CommonRepos implements LaboRelinkRepositoryInterface
{

    const ENTITY_CLASS = LaboRelink::class;
    const NAME = 'laborelink';

}