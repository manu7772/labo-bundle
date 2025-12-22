<?php

namespace Aequation\LaboBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserChecker implements UserCheckerInterface
{

    public function __construct(
        protected LaboUserServiceInterface $userService
    ) {}

    public function checkPreAuth(UserInterface $user): void
    {
        $this->userService->checkUserExceptionAgainstStatus($user);
        return;
    }

    public function checkPostAuth(
        UserInterface $user,
        ?TokenInterface $token = null
    ): void {
        $this->userService->checkUserExceptionAgainstStatus($user);
        return;
    }
}
