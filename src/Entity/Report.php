<?php

namespace App\Entity;

use App\Repository\ReportRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ORM\Table(
    name: 'reports',
    indexes: [
        new ORM\Index(name: 'idx_reports_customer_id', columns: ['customer_id']),
        new ORM\Index(name: 'idx_reports_status', columns: ['status']),
    ],
)]
class Report
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'reports')]
    #[ORM\JoinColumn(name: 'customer_id', nullable: false, onDelete: 'CASCADE')]
    private Customer $customer;

    #[ORM\Column(length: 13, options: ['default' => 'Inconvenience'], columnDefinition: "ENUM('Inconvenience','Review') NOT NULL DEFAULT 'Inconvenience'")]
    private string $type;

    #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true], columnDefinition: 'TINYINT UNSIGNED DEFAULT NULL')]
    private ?int $rating;

    #[ORM\Column(length: 200)]
    private string $subject;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(length: 8, options: ['default' => 'New'], columnDefinition: "ENUM('New','Read','Resolved') NOT NULL DEFAULT 'New'")]
    private string $status = 'New';

    #[ORM\Column(name: 'admin_reply', type: 'text', nullable: true)]
    private ?string $adminReply = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', columnDefinition: 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', columnDefinition: 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')]
    private DateTimeImmutable $updatedAt;

    public function __construct(Customer $customer, string $type, ?int $rating, string $subject, string $message)
    {
        $this->customer = $customer;
        $this->type = $type;
        $this->rating = $rating;
        $this->subject = $subject;
        $this->message = $message;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
        $customer->addReport($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setStatusAndReply(string $status, ?string $reply): void
    {
        $this->status = $status;
        $this->adminReply = $reply;
        $this->updatedAt = new DateTimeImmutable();
    }
}
