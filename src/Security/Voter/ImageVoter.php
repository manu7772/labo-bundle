<?php
namespace Aequation\LaboBundle\Security\Voter;

use Aequation\LaboBundle\Model\Interface\ImageInterface;
use Aequation\LaboBundle\Security\Voter\Base\BaseVoter;
use Aequation\LaboBundle\Service\Tools\HttpRequest;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ImageVoter extends BaseVoter
{

    public const INTERFACE = ImageInterface::class;

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
                    // switch ($attribute) {
                    //     // --- MAIN/PUBLIC SIDE -----------------------------------------------------------------
                    //     case static::ACTION_LIST:
                    //     case static::MAIN_ACTION_LIST:
                    //         $vote = false;
                    //         break;
                    //     case static::ACTION_CREATE:
                    //     case static::MAIN_ACTION_CREATE:
                    //         $vote = false;
                    //         break;
                    //     case static::ACTION_DUPLICATE:
                    //     case static::MAIN_ACTION_DUPLICATE:
                    //         $vote = false;
                    //         break;
                    //     case static::ACTION_READ:
                    //     case static::MAIN_ACTION_READ:
                    //         $vote = false;
                    //         break;
                    //     case static::ACTION_UPDATE:
                    //     case static::MAIN_ACTION_UPDATE:
                    //         $vote = false;
                    //         break;
                    //     case static::ACTION_DELETE:
                    //     case static::MAIN_ACTION_DELETE:
                    //         $vote = false;
                    //         break;
                    // }        
                    break;
                default:
                    # admin and others
                    switch ($attribute) {
                        // --- ADMIN SIDE -----------------------------------------------------------------------
                        // case static::ACTION_LIST:
                        // case static::ADMIN_ACTION_LIST:
                        //     $vote = $this->isGranted('ROLE_EDITOR');
                        //     break;
                        // case static::ACTION_CREATE:
                        // case static::ADMIN_ACTION_CREATE:
                        //     $vote = $this->isGranted('ROLE_EDITOR');
                        //     break;
                        // case static::ACTION_READ:
                        // case static::ADMIN_ACTION_READ:
                        //     $vote = $this->isGranted('ROLE_EDITOR');
                        //     break;
                        case static::ACTION_DUPLICATE:
                        case static::ADMIN_ACTION_DUPLICATE:
                            $vote = false;
                            break;
                        // case static::ACTION_UPDATE:
                        // case static::ADMIN_ACTION_UPDATE:
                        //     $vote = $this->isGranted('ROLE_EDITOR');
                        //     break;
                        // case static::ACTION_DELETE:
                        // case static::ADMIN_ACTION_DELETE:
                        //     $vote = $this->isGranted('ROLE_EDITOR');
                        //     break;
                    }
                    break;
            }
        }
        return $vote;
    }
}
