<?php
namespace Aequation\LaboBundle\Security\Voter;

use Aequation\LaboBundle\Service\Tools\HttpRequest;
use Aequation\LaboBundle\Security\Voter\Base\BaseVoter;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Model\Interface\SiteparamsInterface;

use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SiteparamsVoter extends BaseVoter
{

    public const INTERFACE = SiteparamsInterface::class;

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
        TokenInterface $token,
        ?Vote $vote = null
    ): bool
    {
        $vote = parent::voteOnAttribute($attribute, $subject, $token, $vote);
        if(!$vote) return false;
        /** @var LaboUserInterface */
        $user = $token->getUser();
        if($this->isVoterNeeded($user)) {
            switch ($this->getFirewallOfAction($attribute)) {
                case static::MAIN_FW_ACTIONS:
                    // $vote = false;
                    switch ($attribute) {
                        // --- MAIN/PUBLIC SIDE -----------------------------------------------------------------
                        case static::ACTION_LIST:
                        case static::MAIN_ACTION_LIST:
                            $vote = false;
                            break;
                        case static::ACTION_CREATE:
                        case static::MAIN_ACTION_CREATE:
                            $vote = false;
                            break;
                        case static::ACTION_READ:
                        case static::MAIN_ACTION_READ:
                            $vote = false;
                            break;
                        case static::ACTION_DUPLICATE:
                        case static::MAIN_ACTION_DUPLICATE:
                            $vote = false;
                            break;
                        case static::ACTION_UPDATE:
                        case static::MAIN_ACTION_UPDATE:
                            $vote = false;
                            break;
                        case static::ACTION_SENDMAIL:
                        case static::MAIN_ACTION_SENDMAIL:
                            $vote = false;
                            break;
                        case static::ACTION_DELETE:
                        case static::MAIN_ACTION_DELETE:
                            $vote = false;
                            break;
                    }        
                    break;
                default:
                    $vote = $this->isGranted('ROLE_SUPER_ADMIN');
                    # admin and others
                    switch ($attribute) {
                        // --- ADMIN SIDE -----------------------------------------------------------------------
                        case static::ACTION_LIST:
                        case static::ADMIN_ACTION_LIST:
                            $vote = $this->isGranted('ROLE_SUPER_ADMIN');
                            break;
                        case static::ACTION_CREATE:
                        case static::ADMIN_ACTION_CREATE:
                            $vote = $this->isGranted('ROLE_SUPER_ADMIN');
                            break;
                        case static::ACTION_READ:
                        case static::ADMIN_ACTION_READ:
                            $vote = $this->isGranted('ROLE_SUPER_ADMIN');
                            break;
                        case static::ACTION_DUPLICATE:
                        case static::ADMIN_ACTION_DUPLICATE:
                            $vote = false;
                            break;
                        case static::ACTION_UPDATE:
                        case static::ADMIN_ACTION_UPDATE:
                            $vote = $this->isGranted('ROLE_SUPER_ADMIN');
                            break;
                        case static::ACTION_SENDMAIL:
                        case static::ADMIN_ACTION_SENDMAIL:
                            $vote = false;
                            break;
                        case static::ACTION_DELETE:
                        case static::ADMIN_ACTION_DELETE:
                            $vote = $this->isGranted('ROLE_SUPER_ADMIN');
                            break;
                    }
                    break;
            }
        }
        return $vote;
    }
}
