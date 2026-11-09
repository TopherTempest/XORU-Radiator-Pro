<?php

namespace App\Entity;

use App\Repository\ServiceRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[ORM\Table(name: 'services')]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'service_name', length: 150, unique: true)]
    private string $serviceName;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', columnDefinition: 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP')]
    private DateTimeImmutable $createdAt;

    /** @var Collection<int, Booking> */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'service')]
    private Collection $bookings;

    public function __construct(string $serviceName, ?string $description = null)
    {
        $this->serviceName = $serviceName;
        $this->description = $description;
        $this->createdAt = new DateTimeImmutable();
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }
}
