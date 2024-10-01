<?php

namespace App\Model;

use Symfony\Component\Security\Core\User\UserInterface;

class AuthUser implements UserInterface
{
    public function __construct(
        private string $identifier,
        protected array $roles = ['ROLE_USER']
    ) {}

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function getRoles(): array
    {
        return \array_unique($this->roles);
    }

    public function addRole(string $role): static
    {
        $this->roles[] = $role;

        return $this;
    }

    public function eraseCredentials(): void {}
}
