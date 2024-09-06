<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use Aequation\LaboBundle\Service\Tools\Emails;

class LoginContainer
{

    protected ?string $email = null;
    protected ?string $password = null;
    protected bool $sendmail = false;
    protected bool $valid = false;
    protected ?LaboUserServiceInterface $userService = null;
    // protected ?string $login = null;
    // protected ?string $_csrf_token = null;

    // protected ?AuthenticationException $error = null;

    // protected ?bool $sendmailvalid = false;

    public function __construct(
        ?LaboUserServiceInterface $userService = null,
    )
    {
        if($userService instanceof LaboUserServiceInterface) {
            $this->setUserService($userService);
        } else {
            $this->selfCheck();
        }
    }

    public function __serialize(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
        ];
    }

    public function setUserService(LaboUserServiceInterface $userService): static
    {
        $this->userService = $userService;
        $this->selfCheck();
        return $this;
    }

    public function getUserService(): ?LaboUserServiceInterface
    {
        return $this->userService;
    }

    public function selfCheck(): static
    {
        $this->sendmail = Emails::isEmailValid($this->email) && (!$this->userService || $this->userService->userExists($this->email));
        $this->valid = $this->sendmail && !empty($this->password);
        return $this;
    }

    public function getSendmail(): bool
    {
        $this->selfCheck();
        return $this->sendmail;
    }

    public function isValid(): bool
    {
        $this->selfCheck();
        return $this->valid;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        $this->selfCheck();
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;
        $this->selfCheck();
        return $this;
    }

}