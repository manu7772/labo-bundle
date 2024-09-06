<?php
namespace Aequation\LaboBundle\Security\Voter;

use Aequation\LaboBundle\Model\Interface\CrudvoterInterface;
use Aequation\LaboBundle\Security\Voter\Base\BaseVoter;
use Aequation\LaboBundle\Service\Tools\HttpRequest;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CrudvoterVoter extends BaseVoter
{

    public const INTERFACE = CrudvoterInterface::class;

    /**
     * Vote on attribute
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return boolean
     */
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ): bool
    {
        $vote = parent::voteOnAttribute($attribute, $subject, $token);
        if(!$vote) return false;
        /** @var LaboUserInterface */
        $user = $token->getUser();
        if($this->isVoterNeeded($user)) {
            switch ($this->getFirewallOfAction($attribute)) {
                case static::MAIN_FW_ACTIONS:
                    $vote = false;
                    break;
                default:
                    $vote = $this->isGranted('ROLE_SUPER_ADMIN');
                    // # admin and others
                    // switch ($attribute) {
                    //     // --- ADMIN SIDE -----------------------------------------------------------------------
                    //     case static::ACTION_LIST:
                    //     case static::ADMIN_ACTION_LIST:
                    //         $vote = $this->isGranted('ROLE_SUPER_ADMIN');
                    //         break;
                    //     case static::ACTION_CREATE:
                    //     case static::ADMIN_ACTION_CREATE:
                    //         $vote = $this->isGranted('ROLE_SUPER_ADMIN');
                    //         break;
                    //     case static::ACTION_READ:
                    //     case static::ADMIN_ACTION_READ:
                    //         $vote = $this->isGranted('ROLE_SUPER_ADMIN');
                    //         break;
                    //     case static::ACTION_UPDATE:
                    //     case static::ADMIN_ACTION_UPDATE:
                    //         $vote = $this->isGranted('ROLE_SUPER_ADMIN');
                    //         break;
                    //     case static::ACTION_DELETE:
                    //     case static::ADMIN_ACTION_DELETE:
                    //         $vote = $this->isGranted('ROLE_SUPER_ADMIN');
                    //         break;
                    // }
                    break;
            }
        }
        return $vote;
    }
}
