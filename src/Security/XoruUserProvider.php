<?php

namespace App\Security;

use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class XoruUserProvider implements UserProviderInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        [$accountType, $login] = array_pad(explode(':', $identifier, 2), 2, '');
        if (in_array($accountType, ['login', 'customer'], true)) {
            $row = $this->connection->fetchAssociative(
                'SELECT id, name, email, password FROM customers WHERE email = ? LIMIT 1',
                [$login]
            );
            if ($row && $row['password']) {
                return new XoruUser((int) $row['id'], $identifier, $row['password'], $row['name'], ['ROLE_CUSTOMER']);
            }

        }

        if (in_array($accountType, ['login', 'admin'], true)) {
            $row = $this->connection->fetchAssociative(
                'SELECT id, username, password FROM admins WHERE username = ? LIMIT 1',
                [$login]
            );
            if ($row) {
                return new XoruUser((int) $row['id'], $identifier, $row['password'], $row['username'], ['ROLE_ADMIN']);
            }
        }

        $exception = new UserNotFoundException('The account could not be found.');
        $exception->setUserIdentifier($identifier);
        throw $exception;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof XoruUser) {
            throw new UnsupportedUserException(sprintf('Unsupported user class "%s".', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return XoruUser::class === $class || is_subclass_of($class, XoruUser::class);
    }
}