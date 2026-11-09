<?php

namespace App\Repository;

use App\Entity\Subscriber;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Subscriber> */
final class SubscriberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscriber::class);
    }

    /** @return list<array{id: int, email: string, created_at: string}> */
    public function findNewestRows(): array
    {
        $rows = $this->createQueryBuilder('s')
            ->select('s.id AS id', 's.email AS email', 's.createdAt AS created_at')
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static function (array $row): array {
            if ($row['created_at'] instanceof DateTimeInterface) {
                $row['created_at'] = $row['created_at']->format('Y-m-d H:i:s');
            }

            return $row;
        }, $rows);
    }
}
