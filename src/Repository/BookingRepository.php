<?php

namespace App\Repository;

use App\Entity\Availability;
use App\Entity\Booking;
use App\Entity\Customer;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Booking> */
final class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /** @return list<array<string, mixed>> */
    public function findCustomerRows(int $customerId): array
    {
        $rows = $this->createQueryBuilder('b')
            ->select(
                'b.id AS id',
                'IDENTITY(b.customer) AS customer_id',
                'b.formType AS form_type',
                'b.name AS name',
                'b.phone AS phone',
                'b.vehicle AS vehicle',
                'IDENTITY(b.service) AS service_id',
                'IDENTITY(b.availability) AS availability_id',
                'b.date AS date',
                'b.time AS time',
                'b.message AS message',
                'b.status AS status',
                'b.adminNote AS admin_note',
                'b.createdAt AS created_at',
                'b.updatedAt AS updated_at',
                'b.deletedAt AS deleted_at',
                'b.deletedSlotWasBooked AS deleted_slot_was_booked',
                's.serviceName AS service_name',
            )
            ->join('b.service', 's')
            ->andWhere('b.customer = :customerId')
            ->andWhere('b.deletedAt IS NULL')
            ->setParameter('customerId', $customerId)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_map(self::formatBookingDates(...), $rows);
    }

    /** @return list<array<string, mixed>> */
    public function findAdminRows(bool $trashed): array
    {
        $query = $this->createQueryBuilder('b')
            ->select(
                'b.id AS id',
                'IDENTITY(b.customer) AS customer_id',
                'b.formType AS form_type',
                'b.name AS name',
                'b.phone AS phone',
                'b.vehicle AS vehicle',
                'IDENTITY(b.service) AS service_id',
                'IDENTITY(b.availability) AS availability_id',
                'b.date AS date',
                'b.time AS time',
                'b.message AS message',
                'b.status AS status',
                'b.adminNote AS admin_note',
                'b.createdAt AS created_at',
                'b.updatedAt AS updated_at',
                'b.deletedAt AS deleted_at',
                'b.deletedSlotWasBooked AS deleted_slot_was_booked',
                's.serviceName AS service_name',
                'c.name AS customer_name',
                'c.email AS customer_email',
            )
            ->join('b.service', 's')
            ->join('b.customer', 'c')
            ->orderBy($trashed ? 'b.deletedAt' : 'b.createdAt', 'DESC');

        $query->andWhere($trashed ? 'b.deletedAt IS NOT NULL' : 'b.deletedAt IS NULL');

        return array_map(self::formatBookingDates(...), $query->getQuery()->getArrayResult());
    }

    /** @return list<array<string, mixed>> */
    public function findCustomersWithBookingCounts(): array
    {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'c.id AS id',
                'c.name AS name',
                'c.email AS email',
                'c.provider AS provider',
                'c.createdAt AS created_at',
                'COUNT(b.id) AS booking_count',
            )
            ->from(Customer::class, 'c')
            ->leftJoin('c.bookings', 'b', 'WITH', 'b.deletedAt IS NULL')
            ->groupBy('c.id', 'c.name', 'c.email', 'c.provider', 'c.createdAt')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static function (array $row): array {
            if ($row['created_at'] instanceof DateTimeInterface) {
                $row['created_at'] = $row['created_at']->format('Y-m-d H:i:s');
            }

            return $row;
        }, $rows);
    }

    public function countCreatedByDateRange(DateTimeImmutable $start, DateTimeImmutable $end): int
    {
        $query = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.deletedAt IS NULL')
            ->andWhere('b.createdAt >= :start')
            ->andWhere('b.createdAt < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        return (int) $query->getQuery()->getSingleScalarResult();
    }

    public function countActiveByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.deletedAt IS NULL')
            ->andWhere('b.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findLockedActive(int $id): ?Booking
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.id = :id')
            ->andWhere('b.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    public function findLockedTrashed(int $id): ?Booking
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.id = :id')
            ->andWhere('b.deletedAt IS NOT NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    public function hasOtherActiveBookingForAvailability(Availability $availability, int $bookingId): bool
    {
        return $this->findLockedAvailabilityBooking($availability, $bookingId) !== null;
    }

    public function hasActiveBookingForAvailability(Availability $availability): bool
    {
        return $this->findLockedAvailabilityBooking($availability) !== null;
    }

    private function findLockedAvailabilityBooking(Availability $availability, ?int $exceptBookingId = null): ?Booking
    {
        $query = $this->createQueryBuilder('b')
            ->andWhere('b.availability = :availability')
            ->andWhere('b.deletedAt IS NULL')
            ->setParameter('availability', $availability)
            ->setMaxResults(1);

        if ($exceptBookingId !== null) {
            $query->andWhere('b.id != :bookingId')->setParameter('bookingId', $exceptBookingId);
        }

        return $query->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    /** @param array<string, mixed> $row
     *  @return array<string, mixed>
     */
    private static function formatBookingDates(array $row): array
    {
        foreach (['date' => 'Y-m-d', 'time' => 'H:i:s', 'created_at' => 'Y-m-d H:i:s', 'updated_at' => 'Y-m-d H:i:s', 'deleted_at' => 'Y-m-d H:i:s'] as $field => $format) {
            $value = $row[$field] ?? null;
            if ($value instanceof DateTimeInterface) {
                $row[$field] = $value->format($format);
            }
        }

        return $row;
    }
}
