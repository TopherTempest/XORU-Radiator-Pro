<?php

namespace App\Entity;

use App\Repository\AvailabilityRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvailabilityRepository::class)]
#[ORM\Table(
    name: 'availability',
    uniqueConstraints: [new ORM\UniqueConstraint(name: 'uq_schedule', columns: ['available_date', 'available_time'])],
    indexes: [new ORM\Index(name: 'idx_schedule_date_status', columns: ['available_date', 'status'])],
)]
class Availability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'available_date', type: 'date_immutable')]
    private DateTimeImmutable $availableDate;

    #[ORM\Column(name: 'available_time', type: 'time_immutable')]
    private DateTimeImmutable $availableTime;

    #[ORM\Column(length: 9, options: ['default' => 'Available'], columnDefinition: "ENUM('Available','Booked','Blocked') NOT NULL DEFAULT 'Available'")]
    private string $status = 'Available';

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', columnDefinition: 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP')]
    private DateTimeImmutable $createdAt;

    /** @var Collection<int, Booking> */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'availability')]
    private Collection $bookings;

    public function __construct(DateTimeImmutable $availableDate, DateTimeImmutable $availableTime)
    {
        $this->availableDate = $availableDate;
        $this->availableTime = $availableTime;
        $this->createdAt = new DateTimeImmutable();
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAvailableDate(): DateTimeImmutable
    {
        return $this->availableDate;
    }

    public function getAvailableTime(): DateTimeImmutable
    {
        return $this->availableTime;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}
