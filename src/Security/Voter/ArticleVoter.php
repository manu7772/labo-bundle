<?php
namespace Aequation\LaboBundle\Security\Voter;

use Aequation\LaboBundle\Model\Interface\ArticleInterface;
use Aequation\LaboBundle\Security\Voter\Base\BaseVoter;

class ArticleVoter extends BaseVoter
{

    public const INTERFACE = ArticleInterface::class;

}