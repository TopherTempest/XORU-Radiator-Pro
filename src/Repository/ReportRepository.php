<?php

namespace App\Repository;

use App\Entity\Report;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Report> */
final class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Report::class);
    }

    /** @return list<array<string, mixed>> */
    public function findCustomerRows(int $customerId): array
    {
        return array_map(self::formatCreatedAt(...), $this->createQueryBuilder('r')
            ->select(
                'r.id AS id',
                'IDENTITY(r.customer) AS customer_id',
                'r.type AS type',
                'r.rating AS rating',
                'r.subject AS subject',
                'r.message AS message',
                'r.status AS status',
                'r.adminReply AS admin_reply',
                'r.createdAt AS created_at',
                'r.updatedAt AS updated_at',
            )
            ->andWhere('r.customer = :customerId')
            ->setParameter('customerId', $customerId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult());
    }

    /** @return list<array<string, mixed>> */
    public function findAdminRows(): array
    {
        return array_map(self::formatCreatedAt(...), $this->createQueryBuilder('r')
            ->select(
                'r.id AS id',
                'IDENTITY(r.customer) AS customer_id',
                'r.type AS type',
                'r.rating AS rating',
                'r.subject AS subject',
                'r.message AS message',
                'r.status AS status',
                'r.adminReply AS admin_reply',
                'r.createdAt AS created_at',
                'r.updatedAt AS updated_at',
                'c.name AS customer_name',
                'c.email AS customer_email',
            )
            ->join('r.customer', 'c')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult());
    }

    /** @param array<string, mixed> $row
     *  @return array<string, mixed>
     */
    private static function formatCreatedAt(array $row): array
    {
        foreach (['created_at', 'updated_at'] as $field) {
            if (($row[$field] ?? null) instanceof DateTimeInterface) {
                $row[$field] = $row[$field]->format('Y-m-d H:i:s');
            }
        }

        return $row;
    }
}
