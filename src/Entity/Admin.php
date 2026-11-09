<?php

namespace App\Entity;

use App\Repository\AdminRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminRepository::class)]
#[ORM\Table(name: 'admins')]
class Admin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $username;

    #[ORM\Column(length: 255)]
    private string $password;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', columnDefinition: 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP')]
    private DateTimeImmutable $createdAt;

    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
