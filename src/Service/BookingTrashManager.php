<?php

namespace App\Service;

use App\Repository\AvailabilityRepository;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

final class BookingTrashManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BookingRepository $bookings,
        private readonly AvailabilityRepository $availability,
    ) {
    }

    public function trash(int $bookingId, ?int $customerId = null): void
    {
        $this->entityManager->wrapInTransaction(function () use ($bookingId, $customerId): void {
            $booking = $this->bookings->findLockedActive($bookingId);
            if ($booking === null || ($customerId !== null && $booking->getCustomer()->getId() !== $customerId)) {
                throw new RuntimeException('That booking is no longer available to delete.');
            }

            $slotWasBooked = false;
            $bookingSlot = $booking->getAvailability();
            if ($bookingSlot !== null && $bookingSlot->getId() !== null) {
                $slot = $this->availability->findLocked($bookingSlot->getId());
                if ($slot !== null) {
                    if ($this->bookings->hasOtherActiveBookingForAvailability($slot, $bookingId)) {
                        throw new RuntimeException('This time slot is assigned to another active request, so the booking was not moved to Trash.');
                    }

                    if ($slot->getStatus() === 'Booked') {
                        $slot->setStatus('Available');
                        $slotWasBooked = true;
                    }
                }
            }

            $booking->moveToTrash($slotWasBooked);
        });
    }

    public function restore(int $bookingId): void
    {
        $this->entityManager->wrapInTransaction(function () use ($bookingId): void {
            $booking = $this->bookings->findLockedTrashed($bookingId);
            if ($booking === null) {
                throw new RuntimeException('That booking is no longer in Trash.');
            }

            $bookingSlot = $booking->getAvailability();
            if ($bookingSlot !== null && $booking->getDeletedSlotWasBooked() === true && $bookingSlot->getId() !== null) {
                $slot = $this->availability->findLocked($bookingSlot->getId());
                if ($slot === null || $slot->getStatus() !== 'Available') {
                    throw new RuntimeException('The original time slot is no longer available. This booking remains in Trash.');
                }

                if ($this->bookings->hasActiveBookingForAvailability($slot)) {
                    throw new RuntimeException('The original time slot is already assigned. This booking remains in Trash.');
                }

                $slot->setStatus('Booked');
            }

            $booking->restoreFromTrash();
        });
    }
}
