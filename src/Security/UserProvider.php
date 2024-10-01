<?php

namespace App\Security;

use App\Model\AuthUser;
use App\Util\StringUtil;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    public function __construct(
        private string $permissionID,
        private string $identifierClaim = 'sub'
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $userData = \json_decode($identifier, true);
        if (!\is_array($userData)) throw new UnsupportedUserException();

        $user = new AuthUser($userData[$this->identifierClaim]);

        if (isset($userData['permissions'])) { # Based on Auth0 Symfony SDK
            $userPermissions = [];

            switch (true) {
                case \is_array($userData['permissions']):
                    $userPermissions = (array) $userData['permissions'];
                    break;
                case \is_string($userData['permissions']):
                    $userPermissions = \explode(' ', $userData['permissions']);
                    break;
            }

            $permissionID = \strtoupper($this->permissionID);

            foreach ($userPermissions as $userPermission) {
                $userPermission = \strtoupper($userPermission);

                if ($userPermission == $permissionID) {
                    $user->addRole('ROLE_ADMIN');
                    continue;
                }

                $userPermission = StringUtil::trimPrefix($userPermission, $permissionID . ':');

                if ($userPermission == 'SUPERADMIN') {
                    $user->addRole('ROLE_SUPER_ADMIN');
                    continue;
                }

                $user->addRole('ROLE_' . \str_replace([':', '-'], '_', $userPermission));
            }
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof AuthUser) {
            throw new UnsupportedUserException();
        }

        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return AuthUser::class === $class || \is_subclass_of($class, AuthUser::class);
    }
}
