<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Entity\LaboUser;
use Aequation\LaboBundle\Model\Interface\LaboCategoryInterface;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Repository\LaboUserRepository;
use Aequation\LaboBundle\Service\Tools\Emails;
use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use Aequation\LaboBundle\Service\Interface\AppRoleHierarchyInterface;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
// Symfony
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use PharIo\Manifest\InvalidEmailException;
// PHP
use DateTimeImmutable;

#[AsAlias(LaboUserServiceInterface::class, public: true)]
class LaboUserService extends AppEntityManager implements LaboUserServiceInterface
{

    public const ENTITY = LaboUser::class;

    public function __construct(
        protected EntityManagerInterface $em,
        protected AppServiceInterface $appService,
        protected AccessDecisionManagerInterface $accessDecisionManager,
        protected ValidatorInterface $validator,
        protected Security $security,
        protected LaboUserRepository $userRepository,
        protected AppRoleHierarchyInterface $roleHierarchy,
    ) {
        parent::__construct($em, $appService, $accessDecisionManager, $validator);
    }

    /**
     * Does User can log his account?
     * @param array|LaboUserInterface $user
     * @return bool
     */
    public function isLoggable(
        array|LaboUserInterface $user,
    ): bool
    {
        return $this->isUserEnabled($user);
    }

    /**
     * Is current User valid for navigate as logged User
     * Checks if not expired during navigation
     * @return boolean
     */
    public function isCurrentUserLoggable(): bool
    {
        $user = $this->getUser();
        return $user instanceof LaboUserInterface
            ? $this->isLoggable($user)
            : true; // No current User, returns TRUE
    }

    /**
     * Is User enabled (and not softdeleted)
     * @param array|User $user
     * @return bool
     */
    public function isUserEnabled(
        array|LaboUserInterface $user,
    ): bool
    {
        $expired = false;
        if(is_array($user) && $user['expiresAt']) {
            if(is_string($user['expiresAt'])) $user['expiresAt'] = new DateTimeImmutable($user['expiresAt']);
            $expired = new DateTimeImmutable('NOW') >= $user['expiresAt'];
        }
        switch (true) {
            case $user instanceof LaboUserInterface && !$user->canLogin():            
            case is_array($user) && !$user['enabled']:
            case is_array($user) && $user['softdeleted']:
            case $expired:
                return false;
        }
        return true;
    }

    /**
     * Returns User if not disabled and not softdeleted
     * @param array|User $user
     * @return mixed
     */
    public function filterUserDisabled(
        array|LaboUserInterface $user,
    ): mixed
    {
        return $user = $this->isUserEnabled($user) ? $user : null;
    }

    public function checkUserExceptionAgainstStatus(
        LaboUserInterface $user,
    ): void
    {
        if($user instanceof LaboUserInterface) {
            if(!$user->canLogin()) {
                switch (true) {
                    case $user->isExpired():
                        throw new CustomUserMessageAccountStatusException('Ce compte a expiré et ne peut plus se connecter');
                        break;
                    case $user->isDisabled():
                        throw new CustomUserMessageAccountStatusException('Ce compte est désactivé et ne peut plus se connecter.');
                        break;
                    default:
                        throw new CustomUserMessageAccountStatusException('Ce compte ne peut se connecter.');
                        break;
                }
            }
        }
    }

    public function updateLastLogin(
        LaboUserInterface $user
    ): static
    {
        $user->updateLastLogin();
        $this->save($user);
        return $this;
    }


/***************************************************************** */

    public function logout(bool $validateCsrfToken = true): ?Response
    {
        return $this->security->logout($validateCsrfToken);
    }

    public function getRoleHierarchy(): AppRoleHierarchyInterface
    {
        return $this->roleHierarchy;
    }


    /**
     * Find one User by email or id
     * @param string|integer $emailOrId
     * @param boolean $excludeDisabled = false
     * @return LaboUserInterface|null
     */
    public function findUser(
        string|int $emailOrId,
        bool $excludeDisabled = false,
    ): LaboUserInterface|null
    {
        if(preg_match('/^\\d+$/', (string)$emailOrId)) {
            // Is ID
            $emailOrId = intval($emailOrId);
            $user = $this->userRepository->find($emailOrId);
        } else {
            if(!Emails::isEmailValid($emailOrId)) {
                // Check email validity
                throw new InvalidEmailException(vsprintf('Ce mail "%s" est invalide !', [$emailOrId]));
            }
            // Try find user by email
            $user = $this->userRepository->findOneBy(['email' => $emailOrId]);
        }
        if(!($user instanceof LaboUserInterface)) $user = null;
        return $excludeDisabled && $user instanceof LaboUserInterface ? $this->filterUserDisabled($user) : $user;
    }

    public function findUsersByCategories(
        string|LaboCategoryInterface|iterable $categorys,
        bool $onlyActive = true
    ): array
    {
        $cats = (array)$categorys;
        $categorys = [];
        foreach ($cats as $category) {
            if(is_string($category) || is_int($category)) $categorys[$category] = $category;
            if($category instanceof LaboCategoryInterface) {
                $categorys[$category->getName()] = $category->getName();
                $categorys[$category->getSlug()] = $category->getSlug();
                $categorys[$category->getId()] = $category->getId();
            }
        }
        $users = [];
        /** @var Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository */
        $repo = $this->getRepository();
        foreach ($repo->findAll() as $user) {
            /** @var User $user */
            if(!$onlyActive || $user->isActive()) {
                foreach ($user->getCategorys() as $category) {
                    $tests = [
                        $category->getSlug(),
                        $category->getName(),
                        $category->getId(),
                    ];
                    if(count(array_intersect($categorys, $tests))) {
                        $users[$user->getId()] = $user;
                    }
                }
            }
        }
        return $users;
    }

    public function findUserSimpleData(
        string|int $value,
        bool $discardDisabled = false,
    ): array
    {
        return $this->userRepository->findUserSimpleData($value, $discardDisabled);
    }

    /**
     * User exists?
     * @param string|int $value
     * @param bool $contextFilter = false
     * @return bool
     */
    public function userExists(
        string|int $value,
        bool $contextFilter = false
    ): bool
    {
        return $this->userRepository->userExists($value, $contextFilter);
    }

    /**
     * Add me to SUPER ADMIN
     * @return LaboUserInterface|null
     */
    public function addMeToSuperAdmin(): ?LaboUserInterface
    {
        /** @var LaboUserInterface */
        $admin = $this->getMainSAdmin();
        if($admin instanceof LaboUserInterface && (!$admin->hasRole('ROLE_SUPER_ADMIN') || !$admin->isActive())) {
            $admin->addRole('ROLE_SUPER_ADMIN');
            $admin->setEnabled(true);
            $admin->setSoftdeleted(false);
            $this->em->flush();
            return $admin;
        }
        return null;
    }

    public function getMainSAdmin(): ?LaboUserInterface
    {
        $admin_email = $this->appService->getParam('main_sadmin');
        return $this->findUser($admin_email);
    }

    public function getMainAdmin(): ?LaboUserInterface
    {
        $admin_email = $this->appService->getParam('main_admin');
        return $this->findUser($admin_email);
    }

    /**
     * Try to find User by email, then create and persist it if not found
     * @param string $email
     * @return LaboUserInterface
     */
    public function FindOrCreateUserByEmail(
        ?string $email,
    ): LaboUserInterface|false
    {
        if(!Emails::isEmailValid($email)) return false;
        /** @var ?LaboUserInterface $user */
        $user = $this->userRepository->findOneByEmail($email);
        if($user instanceof LaboUserInterface) return $user;
        // Not found, then create new User
        /** @var User */
        $user = $this->getNew();
        $user->setEmail($email);
        $user->setFirstname(Emails::emailToFakeName(email: $email));
        $user->autoGeneratePassword();
        // Validate
        // /** @var ConstraintViolationListInterface */
        // $errors = $this->validator->validate($user);
        // if($errors->count() > 0) {
        //     throw new \Exception($errors->__toString());
        // }
        $this->em->persist($user);
        // $this->em->flush();
        return $user;
    }


}