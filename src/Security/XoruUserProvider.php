<?php

namespace App\Security;

use App\Entity\Admin;
use App\Entity\Customer;
use App\Repository\AdminRepository;
use App\Repository\CustomerRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class XoruUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly CustomerRepository $customers,
        private readonly AdminRepository $admins,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        [$accountType, $login] = array_pad(explode(':', $identifier, 2), 2, '');
        if (in_array($accountType, ['login', 'customer'], true)) {
            $customer = $this->customers->findOneBy(['email' => $login]);
            if ($customer instanceof Customer && $customer->getPassword() !== null && $customer->getId() !== null) {
                return new XoruUser(
                    $customer->getId(),
                    $identifier,
                    $customer->getPassword(),
                    $customer->getName(),
                    ['ROLE_CUSTOMER'],
                );
            }
        }

        if (in_array($accountType, ['login', 'admin'], true)) {
            $admin = $this->admins->findOneBy(['username' => $login]);
            if ($admin instanceof Admin && $admin->getId() !== null) {
                return new XoruUser(
                    $admin->getId(),
                    $identifier,
                    $admin->getPassword(),
                    $admin->getUsername(),
                    ['ROLE_ADMIN'],
                );
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