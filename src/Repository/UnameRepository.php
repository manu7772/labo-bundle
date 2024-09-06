<?php
namespace Aequation\LaboBundle\Repository;

use Aequation\LaboBundle\Entity\Uname;
use Aequation\LaboBundle\Repository\Base\CommonRepos;
use Aequation\LaboBundle\Repository\Interface\UnameRepositoryInterface;

/**
 * @extends CommonRepos<Uname>
 */
class UnameRepository extends CommonRepos implements UnameRepositoryInterface
{

    const ENTITY_CLASS = Uname::class;
    const NAME = 'uname';

}
