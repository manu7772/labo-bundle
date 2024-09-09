<?php
namespace Aequation\LaboBundle\Security\Voter;

use Aequation\LaboBundle\Model\Final\FinalEntrepriseInterface;
use Aequation\LaboBundle\Security\Voter\Base\BaseVoter;
use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use Aequation\LaboBundle\Model\Final\FinalUserInterface;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Security\Voter\Interface\UserVoterInterface;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

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
        TokenInterface $token
    ): bool
    {
        $vote = parent::voteOnAttribute($attribute, $subject, $token);
        if(!$vote) return false;
        /** @var LaboUserInterface */
        $user = $token->getUser();
        /** @var FinalEntrepriseInterface */
        $object = $this->getSubjectAsObject($subject, $this->manager);
        if($this->isVoterNeeded($user)) {
            switch ($this->getFirewallOfAction($attribute)) {
                case static::MAIN_FW_ACTIONS:
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
                            $vote = $user === $object || ($user instanceof FinalUserInterface && $user->hasEntreprise($object));
                            break;
                        case static::ACTION_DUPLICATE:
                        case static::MAIN_ACTION_DUPLICATE:
                            $vote = false;
                            break;
                        case static::ACTION_UPDATE:
                        case static::MAIN_ACTION_UPDATE:
                            $vote = $user === $object;
                            break;
                        case static::ACTION_SENDMAIL:
                        case static::MAIN_ACTION_SENDMAIL:
                            $vote = $user === $object;
                            break;
                        case static::ACTION_DELETE:
                        case static::MAIN_ACTION_DELETE:
                            $vote = $user === $object;
                            break;
                    }
                    break;
                default:
                    # admin and others
                    switch ($attribute) {
                        // --- ADMIN SIDE -----------------------------------------------------------------------
                        case static::ACTION_LIST:
                        case static::ADMIN_ACTION_LIST:
                            $vote = $this->isGranted('ROLE_COLLABORATOR');
                            break;
                        case static::ACTION_CREATE:
                        case static::ADMIN_ACTION_CREATE:
                            $vote = $this->isGranted('ROLE_EDITOR') && !($user instanceof FinalEntrepriseInterface);
                            break;
                        case static::ACTION_READ:
                        case static::ADMIN_ACTION_READ:
                            $vote = $this->isGranted('ROLE_COLLABORATOR');
                            break;
                        case static::ACTION_DUPLICATE:
                        case static::ADMIN_ACTION_DUPLICATE:
                            $vote = false;
                            break;
                        case static::ACTION_UPDATE:
                        case static::ADMIN_ACTION_UPDATE:
                            $vote = $this->isGranted($object) && $this->isGranted('ROLE_EDITOR') && !($user instanceof FinalEntrepriseInterface);
                            break;
                        case static::ACTION_SENDMAIL:
                        case static::ADMIN_ACTION_SENDMAIL:
                            $vote = $this->isGranted($object) || $this->isGranted('ROLE_COLLABORATOR');
                            break;
                        case static::ACTION_DELETE:
                        case static::ADMIN_ACTION_DELETE:
                            $vote = $user === $object || ($this->isGranted($object) && $this->isGranted('ROLE_ADMIN')) && !($user instanceof FinalEntrepriseInterface);
                            break;
                    }
                    break;
            }
        }
        return $vote;
    }

}