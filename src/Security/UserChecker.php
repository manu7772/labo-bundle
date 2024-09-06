<?php
namespace Aequation\LaboBundle\Security;

use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use Aequation\LaboBundle\Service\LaboUserService;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{

    public function __construct(
        protected LaboUserServiceInterface $userService
    )
    {
        
    }

    public function checkPreAuth(UserInterface $user): void
    {
        $this->userService->checkUserExceptionAgainstStatus($user);
        return;
    }

    public function checkPostAuth(UserInterface $user): void
    {
        $this->userService->checkUserExceptionAgainstStatus($user);
        return;
    }

}