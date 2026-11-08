<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class XoruUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private readonly int $id,
        private readonly string $userIdentifier,
        private readonly string $password,
        private readonly string $displayName,
        private readonly array $roles,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRoles(): array
    {
        return array_unique($this->roles);
    }

    public function eraseCredentials(): void
    {
    }
}