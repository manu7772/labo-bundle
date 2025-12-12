<?php
namespace Aequation\LaboBundle\Security\Voter;

use Aequation\LaboBundle\Security\Voter\Base\BaseVoter;
use Aequation\LaboBundle\Model\Final\FinalUserInterface;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Aequation\LaboBundle\Model\Final\FinalEntrepriseInterface;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;

use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use Aequation\LaboBundle\Security\Voter\Interface\UserVoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

#[AsAlias(UserVoterInterface::class, public: true)]
class UserVoter extends BaseVoter
{

    public const INTERFACE = FinalUserInterface::class;

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
        $voted = parent::voteOnAttribute($attribute, $subject, $token, $vote);
        if(!$voted) return false;
        /** @var LaboUserInterface */
        $user = $token->getUser();
        /** @var FinalUserInterface */
        $object = $this->getSubjectAsObject($subject, $this->manager);
        if($this->isVoterNeeded($user)) {
            switch ($this->getFirewallOfAction($attribute)) {
                case static::MAIN_FW_ACTIONS:
                    switch ($attribute) {
                        // --- MAIN/PUBLIC SIDE -----------------------------------------------------------------
                        case static::ACTION_LIST:
                        case static::MAIN_ACTION_LIST:
                            $voted = false;
                            if(!$voted) {
                                $vote?->addReason('Listing users is not allowed on main firewall (' . $this->getFirewallOfAction($attribute) . ').');
                            }
                            break;
                        case static::ACTION_CREATE:
                        case static::MAIN_ACTION_CREATE:
                            $voted = false;
                            if(!$voted) {
                                $vote?->addReason('Creating users is not allowed on main firewall.');
                            }
                            break;
                        case static::ACTION_READ:
                        case static::MAIN_ACTION_READ:
                            $voted = $user === $object || ($user instanceof FinalEntrepriseInterface && $user->hasMember($object));
                            if(!$voted) {
                                $vote?->addReason('You can only read your own user or users of your entreprise.');
                            }
                            break;
                        case static::ACTION_DUPLICATE:
                        case static::MAIN_ACTION_DUPLICATE:
                            $voted = false;
                            if(!$voted) {
                                $vote?->addReason('Duplicating users is not allowed on main firewall.');
                            }
                            break;
                        case static::ACTION_UPDATE:
                        case static::MAIN_ACTION_UPDATE:
                            $voted = $user === $object;
                            if(!$voted) {
                                $vote?->addReason('You can only update your own user.');
                            }
                            break;
                        case static::ACTION_SENDMAIL:
                        case static::MAIN_ACTION_SENDMAIL:
                            $voted = $user === $object;
                            if(!$voted) {
                                $vote?->addReason('You can only send emails to your own user.');
                            }
                            break;
                        case static::ACTION_DELETE:
                        case static::MAIN_ACTION_DELETE:
                            $voted = $user === $object;
                            if(!$voted) {
                                $vote?->addReason('You can only delete your own user.');
                            }
                            break;
                    }
                    break;
                default:
                    # admin and others
                    switch ($attribute) {
                        // --- ADMIN SIDE -----------------------------------------------------------------------
                        case static::ACTION_LIST:
                        case static::ADMIN_ACTION_LIST:
                            $voted = $this->isGranted('ROLE_COLLABORATOR');
                            if(!$voted) {
                                $vote?->addReason('You need the ROLE_COLLABORATOR role to list users.');
                            }
                            break;
                        case static::ACTION_CREATE:
                        case static::ADMIN_ACTION_CREATE:
                            $voted = $this->isGranted('ROLE_EDITOR') && !($user instanceof FinalEntrepriseInterface);
                            if(!$voted) {
                                $vote?->addReason('You need the ROLE_EDITOR role and not be an entreprise to create users.');
                            }
                            break;
                        case static::ACTION_READ:
                        case static::ADMIN_ACTION_READ:
                            $voted = $user === $object || $this->isGranted('ROLE_COLLABORATOR');
                            if(!$voted) {
                                $vote?->addReason('You can only read your own user or you need the ROLE_COLLABORATOR role.');
                            }
                            break;
                        case static::ACTION_DUPLICATE:
                        case static::ADMIN_ACTION_DUPLICATE:
                            $voted = false;
                            if(!$voted) {
                                $vote?->addReason('Duplicating users is not allowed.');
                            }
                            break;
                        case static::ACTION_UPDATE:
                        case static::ADMIN_ACTION_UPDATE:
                            $voted = $user === $object || ($this->isGranted($object) && $this->isGranted('ROLE_EDITOR')) && !($user instanceof FinalEntrepriseInterface);
                            if(!$voted) {
                                $vote?->addReason('You can only update your own user or you need the ROLE_EDITOR role and not be an entreprise.');
                            }
                            break;
                        case static::ACTION_SENDMAIL:
                        case static::ADMIN_ACTION_SENDMAIL:
                            $voted = $user === $object || ($this->isGranted($object) && $this->isGranted('ROLE_COLLABORATOR'));
                            if(!$voted) {
                                $vote?->addReason('You can only send emails to your own user or you need the ROLE_COLLABORATOR role.');
                            }
                            break;
                        case static::ACTION_DELETE:
                        case static::ADMIN_ACTION_DELETE:
                            $voted = $user === $object || ($this->isGranted($object) && $this->isGranted('ROLE_ADMIN')) && !($user instanceof FinalEntrepriseInterface);
                            if(!$voted) {
                                $vote?->addReason('You can only delete your own user or you need the ROLE_ADMIN role and not be an entreprise.');
                            }
                            break;
                    }
                    break;
            }
        }
        if($vote && $this->appService->isDev() && !$voted) {
            dump($vote->reasons);
        }
        return $voted;
    }

}