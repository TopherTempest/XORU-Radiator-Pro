<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(
    name: 'bookings',
    indexes: [
        new ORM\Index(name: 'idx_bookings_customer_id', columns: ['customer_id']),
        new ORM\Index(name: 'idx_bookings_service_id', columns: ['service_id']),
        new ORM\Index(name: 'idx_bookings_availability_id', columns: ['availability_id']),
        new ORM\Index(name: 'idx_bookings_status', columns: ['status']),
        new ORM\Index(name: 'idx_bookings_deleted_at', columns: ['deleted_at']),
    ],
)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'customer_id', nullable: false, onDelete: 'CASCADE')]
    private Customer $customer;

    #[ORM\Column(name: 'form_type', length: 8, options: ['default' => 'booking'], columnDefinition: "ENUM('booking','estimate') NOT NULL DEFAULT 'booking'")]
    private string $formType;

    #[ORM\Column(length: 150)]
    private string $name;

    #[ORM\Column(length: 50)]
    private string $phone;

    #[ORM\Column(length: 150)]
    private string $vehicle;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'service_id', nullable: false, onDelete: 'RESTRICT')]
    private Service $service;

    #[ORM\ManyToOne(targetEntity: Availability::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'availability_id', nullable: true, onDelete: 'SET NULL')]
    private ?Availability $availability;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $date;

    #[ORM\Column(type: 'time_immutable', nullable: true)]
    private ?DateTimeImmutable $time;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(length: 9, options: ['default' => 'Pending'], columnDefinition: "ENUM('Pending','Approved','Declined','Completed','Cancelled') NOT NULL DEFAULT 'Pending'")]
    private string $status = 'Pending';

    #[ORM\Column(name: 'admin_note', type: 'text', nullable: true)]
    private ?string $adminNote = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', columnDefinition: 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', columnDefinition: 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')]
    private DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'deleted_at', type: 'datetime_immutable', nullable: true, columnDefinition: 'DATETIME DEFAULT NULL')]
    private ?DateTimeImmutable $deletedAt = null;

    #[ORM\Column(name: 'deleted_slot_was_booked', type: 'boolean', nullable: true, columnDefinition: 'TINYINT(1) DEFAULT NULL')]
    private ?bool $deletedSlotWasBooked = null;

    public function __construct(
        Customer $customer,
        string $formType,
        string $name,
        string $phone,
        string $vehicle,
        Service $service,
        ?Availability $availability,
        ?DateTimeImmutable $date,
        ?DateTimeImmutable $time,
        string $message,
    ) {
        $this->customer = $customer;
        $this->formType = $formType;
        $this->name = $name;
        $this->phone = $phone;
        $this->vehicle = $vehicle;
        $this->service = $service;
        $this->availability = $availability;
        $this->date = $date;
        $this->time = $time;
        $this->message = $message;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
        $customer->addBooking($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function getService(): Service
    {
        return $this->service;
    }

    public function getFormType(): string
    {
        return $this->formType;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getAvailability(): ?Availability
    {
        return $this->availability;
    }

    public function getDate(): ?DateTimeImmutable
    {
        return $this->date;
    }

    public function getTime(): ?DateTimeImmutable
    {
        return $this->time;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function getDeletedSlotWasBooked(): ?bool
    {
        return $this->deletedSlotWasBooked;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setEstimateResponse(string $response): void
    {
        if ($this->formType !== 'estimate') {
            throw new \LogicException('Only estimate requests can receive an estimate response.');
        }

        $this->adminNote = $response;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setSchedule(Availability $availability, string $adminNote): void
    {
        $this->availability = $availability;
        $this->date = $availability->getAvailableDate();
        $this->time = $availability->getAvailableTime();
        $this->status = 'Pending';
        $this->adminNote = $adminNote;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function moveToTrash(bool $slotWasBooked): void
    {
        $this->deletedAt = new DateTimeImmutable();
        $this->deletedSlotWasBooked = $slotWasBooked;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function restoreFromTrash(): void
    {
        $this->deletedAt = null;
        $this->deletedSlotWasBooked = null;
        $this->updatedAt = new DateTimeImmutable();
    }
}
