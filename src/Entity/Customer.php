<?php

namespace App\Entity;

use App\Repository\CustomerRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\Table(name: 'customers', indexes: [new ORM\Index(name: 'idx_customer_provider', columns: ['provider', 'provider_id'])])]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $name;

    #[ORM\Column(length: 190, unique: true)]
    private string $email;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password;

    #[ORM\Column(length: 8, options: ['default' => 'email'], columnDefinition: "ENUM('email','google','facebook') NOT NULL DEFAULT 'email'")]
    private string $provider;

    #[ORM\Column(name: 'provider_id', length: 255, nullable: true)]
    private ?string $providerId;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', columnDefinition: 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', columnDefinition: 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')]
    private DateTimeImmutable $updatedAt;

    /** @var Collection<int, Booking> */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'customer')]
    private Collection $bookings;

    /** @var Collection<int, Report> */
    #[ORM\OneToMany(targetEntity: Report::class, mappedBy: 'customer')]
    private Collection $reports;

    public function __construct(
        string $name,
        string $email,
        ?string $password = null,
        string $provider = 'email',
        ?string $providerId = null,
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->provider = $provider;
        $this->providerId = $providerId;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
        $this->bookings = new ArrayCollection();
        $this->reports = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function addBooking(Booking $booking): void
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
        }
    }

    public function addReport(Report $report): void
    {
        if (!$this->reports->contains($report)) {
            $this->reports->add($report);
        }
    }
}
