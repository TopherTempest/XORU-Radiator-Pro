<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use RuntimeException;
use Throwable;

final class BookingTrashManager
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function trash(int $bookingId, ?int $customerId = null): void
    {
        $this->connection->beginTransaction();

        try {
            $sql = 'SELECT availability_id FROM bookings WHERE id = ? AND deleted_at IS NULL';
            $params = [$bookingId];
            if ($customerId !== null) {
                $sql .= ' AND customer_id = ?';
                $params[] = $customerId;
            }

            $booking = $this->connection->fetchAssociative($sql.' FOR UPDATE', $params);
            if (!$booking) {
                throw new RuntimeException('That booking is no longer available to delete.');
            }

            $slotWasBooked = false;
            if ($booking['availability_id'] !== null) {
                $otherBooking = $this->connection->fetchOne(
                    'SELECT id FROM bookings WHERE availability_id = ? AND id <> ? AND deleted_at IS NULL FOR UPDATE',
                    [$booking['availability_id'], $bookingId]
                );
                if ($otherBooking) {
                    throw new RuntimeException('This time slot is assigned to another active request, so the booking was not moved to Trash.');
                }

                $slotWasBooked = $this->connection->executeStatement(
                    "UPDATE availability SET status = 'Available' WHERE id = ? AND status = 'Booked'",
                    [$booking['availability_id']]
                ) > 0;
            }

            $this->connection->update('bookings', [
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_slot_was_booked' => (int) $slotWasBooked,
            ], ['id' => $bookingId]);

            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }
    }

    public function restore(int $bookingId): void
    {
        $this->connection->beginTransaction();

        try {
            $booking = $this->connection->fetchAssociative(
                'SELECT availability_id, deleted_slot_was_booked FROM bookings WHERE id = ? AND deleted_at IS NOT NULL FOR UPDATE',
                [$bookingId]
            );
            if (!$booking) {
                throw new RuntimeException('That booking is no longer in Trash.');
            }

            if ($booking['availability_id'] !== null && (bool) $booking['deleted_slot_was_booked']) {
                $slot = $this->connection->fetchAssociative(
                    'SELECT status FROM availability WHERE id = ? FOR UPDATE',
                    [$booking['availability_id']]
                );
                if (!$slot || $slot['status'] !== 'Available') {
                    throw new RuntimeException('The original time slot is no longer available. This booking remains in Trash.');
                }

                $activeBooking = $this->connection->fetchOne(
                    'SELECT id FROM bookings WHERE availability_id = ? AND deleted_at IS NULL FOR UPDATE',
                    [$booking['availability_id']]
                );
                if ($activeBooking) {
                    throw new RuntimeException('The original time slot is already assigned. This booking remains in Trash.');
                }

                $this->connection->update('availability', ['status' => 'Booked'], ['id' => $booking['availability_id']]);
            }

            $this->connection->update('bookings', [
                'deleted_at' => null,
                'deleted_slot_was_booked' => null,
            ], ['id' => $bookingId]);

            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }
    }
}
