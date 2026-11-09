<?php

namespace App\Repository;

use App\Entity\Availability;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Availability> */
final class AvailabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Availability::class);
    }

    /** @return list<array{id: int, available_date: string, available_time: string, status?: string}> */
    public function findUpcomingRows(bool $availableOnly = false): array
    {
        $query = $this->createQueryBuilder('a')
            ->select('a.id AS id', 'a.availableDate AS available_date', 'a.availableTime AS available_time')
            ->andWhere('a.availableDate >= :today')
            ->setParameter('today', new DateTimeImmutable('today'))
            ->orderBy('a.availableDate', 'ASC')
            ->addOrderBy('a.availableTime', 'ASC');

        if ($availableOnly) {
            $query->andWhere('a.status = :status')->setParameter('status', 'Available');
        } else {
            $query->addSelect('a.status AS status');
        }

        return array_map(self::formatScheduleRow(...), $query->getQuery()->getArrayResult());
    }

    public function findLockedAvailable(int $id): ?Availability
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.id = :id')
            ->andWhere('a.status = :status')
            ->setParameter('id', $id)
            ->setParameter('status', 'Available')
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    public function findLocked(int $id): ?Availability
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    /** @param array<string, mixed> $row
     *  @return array<string, mixed>
     */
    private static function formatScheduleRow(array $row): array
    {
        foreach (['available_date' => 'Y-m-d', 'available_time' => 'H:i:s'] as $field => $format) {
            $value = $row[$field];
            $row[$field] = $value instanceof DateTimeInterface ? $value->format($format) : (string) $value;
        }

        return $row;
    }
}
