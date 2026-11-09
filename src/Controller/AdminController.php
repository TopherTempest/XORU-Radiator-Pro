<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Entity\Availability;
use App\Repository\AvailabilityRepository;
use App\Repository\BookingRepository;
use App\Repository\CustomerRepository;
use App\Repository\ReportRepository;
use App\Repository\SubscriberRepository;
use App\Security\XoruUser;
use App\Service\BookingTrashManager;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    public function dashboard(
        BookingRepository $bookings,
        AvailabilityRepository $availability,
        SubscriberRepository $subscribers,
        CustomerRepository $customers,
        Request $request,
    ): Response {
        $monthStart = new DateTimeImmutable(date('Y-m-01'));
        $nextMonth = $monthStart->modify('first day of next month');

        return $this->render('admin/dashboard.html.twig', [
            'bookings' => $bookings->findAdminRows(trashed: false),
            'trashedBookings' => $bookings->findAdminRows(trashed: true),
            'bookingView' => $request->query->get('tab') === 'trash' ? 'trash' : 'active',
            'slots' => $availability->findUpcomingRows(),
            'freeSlots' => $availability->findUpcomingRows(availableOnly: true),
            'subscribers' => $subscribers->findNewestRows(),
            'metrics' => [
                'monthBookings' => $bookings->countCreatedByDateRange($monthStart, $nextMonth),
                'pending' => $bookings->countActiveByStatus('Pending'),
                'approved' => $bookings->countActiveByStatus('Approved'),
                'customers' => $customers->count([]),
            ],
        ]);
    }

    #[Route('/admin/customers', name: 'admin_customers', methods: ['GET'])]
    public function customers(BookingRepository $bookings): Response
    {
        return $this->render('admin/customers.html.twig', [
            'customers' => $bookings->findCustomersWithBookingCounts(),
        ]);
    }

    #[Route('/admin/reports', name: 'admin_reports', methods: ['GET'])]
    public function reports(ReportRepository $reports): Response
    {
        return $this->render('admin/reports.html.twig', [
            'reports' => $reports->findAdminRows(),
        ]);
    }

    #[Route('/admin/settings', name: 'admin_settings', methods: ['GET'])]
    public function settings(): Response
    {
        return $this->render('admin/settings.html.twig');
    }

    #[Route('/admin/create-admin', name: 'admin_create', methods: ['GET', 'POST'])]
    public function createAdmin(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        CsrfTokenManagerInterface $csrfTokens,
    ): Response {
        $error = '';
        if ($request->isMethod('POST')) {
            if (!$csrfTokens->isTokenValid(new CsrfToken('create_admin', $request->request->getString('_token')))) {
                throw $this->createAccessDeniedException('Invalid request token.');
            }

            $username = trim($request->request->getString('username'));
            $password = $request->request->getString('password');
            $confirmation = $request->request->getString('confirm_password');
            if (strlen($username) < 3 || strlen($username) > 50) {
                $error = 'Username must be between 3 and 50 characters.';
            } elseif (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters.';
            } elseif ($password !== $confirmation) {
                $error = 'Passwords do not match.';
            } else {
                $user = new XoruUser(0, 'admin:'.$username, '', $username, ['ROLE_ADMIN']);
                try {
                    $entityManager->persist(new Admin(
                        $username,
                        $passwordHasher->hashPassword($user, $password),
                    ));
                    $entityManager->flush();
                    $this->addFlash('success', 'Administrator account created.');
                    return $this->redirectToRoute('admin_create');
                } catch (UniqueConstraintViolationException) {
                    $error = 'That admin username already exists.';
                }
            }
        }

        return $this->render('admin/create_admin.html.twig', ['error' => $error]);
    }

    #[Route('/admin/action', name: 'admin_action', methods: ['POST'])]
    public function action(
        Request $request,
        EntityManagerInterface $entityManager,
        BookingRepository $bookings,
        AvailabilityRepository $availability,
        CsrfTokenManagerInterface $csrfTokens,
        BookingTrashManager $trashManager,
    ): Response {
        if (!$csrfTokens->isTokenValid(new CsrfToken('admin_action', $request->request->getString('_token')))) {
            throw $this->createAccessDeniedException('Invalid request token.');
        }

        $action = $request->request->getString('action');
        $bookingId = filter_var($request->request->get('booking_id'), FILTER_VALIDATE_INT);
        $slotId = filter_var($request->request->get('availability_id'), FILTER_VALIDATE_INT);
        $bookingView = in_array($action, ['delete_booking', 'restore_booking'], true) ? 'trash' : 'active';

        try {
            if ($action === 'status' && $bookingId && $bookingId > 0) {
                $status = $request->request->getString('status');
                if (!in_array($status, ['Approved', 'Declined', 'Completed', 'Cancelled'], true)) {
                    throw new \RuntimeException('Invalid booking status.');
                }

                $entityManager->wrapInTransaction(function () use ($bookings, $availability, $bookingId, $status): void {
                    $booking = $bookings->findLockedActive($bookingId);
                    if ($booking === null) {
                        throw new \RuntimeException('Booking not found.');
                    }

                    $booking->setStatus($status);
                    $bookingSlot = $booking->getAvailability();
                    if ($bookingSlot !== null && $bookingSlot->getId() !== null) {
                        $slot = $availability->findLocked($bookingSlot->getId());
                        if ($slot !== null) {
                            $slot->setStatus(in_array($status, ['Declined', 'Cancelled'], true) ? 'Available' : 'Booked');
                        }
                    }
                });
                $this->addFlash('success', 'Request status updated. The customer can see the change in their dashboard.');
            } elseif ($action === 'estimate_response' && $bookingId && $bookingId > 0) {
                $response = trim($request->request->getString('admin_response'));
                if ($response === '' || strlen($response) > 5000) {
                    throw new \RuntimeException('Enter a response of no more than 5,000 characters.');
                }

                $entityManager->wrapInTransaction(function () use ($bookings, $bookingId, $response): void {
                    $booking = $bookings->findLockedActive($bookingId);
                    if ($booking === null || $booking->getFormType() !== 'estimate') {
                        throw new \RuntimeException('Estimate request not found.');
                    }

                    $booking->setEstimateResponse($response);
                });
                $this->addFlash('success', 'Estimate response saved and shared with the customer.');
            } elseif ($action === 'reschedule' && $bookingId && $bookingId > 0 && $slotId && $slotId > 0) {
                $entityManager->wrapInTransaction(function () use ($bookings, $availability, $bookingId, $slotId): void {
                    $booking = $bookings->findLockedActive($bookingId);
                    $newSlot = $availability->findLockedAvailable($slotId);
                    if ($booking === null || $newSlot === null) {
                        throw new \RuntimeException('Booking or new schedule was not available.');
                    }

                    $oldSlot = $booking->getAvailability();
                    if ($oldSlot !== null && $oldSlot->getId() !== null) {
                        $lockedOldSlot = $availability->findLocked($oldSlot->getId());
                        $lockedOldSlot?->setStatus('Available');
                    }

                    $newSlot->setStatus('Booked');
                    $booking->setSchedule($newSlot, 'Booking rescheduled by admin. Awaiting customer confirmation.');
                });
                $this->addFlash('success', 'Booking rescheduled.');
            } elseif ($action === 'delete_booking' && $bookingId && $bookingId > 0) {
                $trashManager->trash($bookingId);
                $this->addFlash('success', 'Booking or estimate moved to Trash.');
            } elseif ($action === 'restore_booking' && $bookingId && $bookingId > 0) {
                $trashManager->restore($bookingId);
                $this->addFlash('success', 'Booking or estimate restored.');
            } elseif ($action === 'add_slot') {
                $date = $request->request->getString('available_date');
                $time = $request->request->getString('available_time');
                $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
                $parsedTime = DateTimeImmutable::createFromFormat('!H:i', $time);
                if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date
                    || !$parsedTime || $parsedTime->format('H:i') !== $time
                    || $date < date('Y-m-d')) {
                    throw new \RuntimeException('Enter a valid future date and time.');
                }

                $entityManager->persist(new Availability($parsedDate, $parsedTime));
                $entityManager->flush();
                $this->addFlash('success', 'New available schedule added.');
            } elseif (in_array($action, ['block_slot', 'unblock_slot'], true) && $slotId && $slotId > 0) {
                $oldStatus = $action === 'block_slot' ? 'Available' : 'Blocked';
                $newStatus = $action === 'block_slot' ? 'Blocked' : 'Available';
                $entityManager->wrapInTransaction(function () use ($availability, $slotId, $oldStatus, $newStatus): void {
                    $slot = $availability->findLocked($slotId);
                    if ($slot !== null && $slot->getStatus() === $oldStatus) {
                        $slot->setStatus($newStatus);
                    }
                });
                $this->addFlash('success', $action === 'block_slot' ? 'Schedule slot blocked.' : 'Schedule slot made available.');
            } else {
                throw new \RuntimeException('Invalid admin action.');
            }
        } catch (\Throwable $exception) {
            $this->addFlash('error', $exception instanceof UniqueConstraintViolationException
                ? 'That date and time already exists.'
                : $exception->getMessage());
        }

        return $this->redirectToRoute('admin_dashboard', ['tab' => $bookingView]);
    }

    #[Route('/admin/report-action', name: 'admin_report_action', methods: ['POST'])]
    public function reportAction(
        Request $request,
        EntityManagerInterface $entityManager,
        ReportRepository $reports,
        CsrfTokenManagerInterface $csrfTokens,
    ): Response {
        if (!$csrfTokens->isTokenValid(new CsrfToken('admin_report', $request->request->getString('_token')))) {
            throw $this->createAccessDeniedException('Invalid request token.');
        }

        $reportId = filter_var($request->request->get('report_id'), FILTER_VALIDATE_INT);
        $status = $request->request->getString('status', 'New');
        $reply = trim($request->request->getString('admin_reply'));
        if (!$reportId || $reportId < 1 || !in_array($status, ['New', 'Read', 'Resolved'], true)) {
            $this->addFlash('error', 'Invalid report update.');
        } else {
            $report = $reports->find($reportId);
            if ($report !== null) {
                $report->setStatusAndReply($status, $reply !== '' ? $reply : null);
                $entityManager->flush();
            }
            $this->addFlash('success', 'Report updated successfully.');
        }

        return $this->redirectToRoute('admin_reports');
    }

    public function getAdminName(): string
    {
        $user = $this->getUser();
        return $user instanceof XoruUser ? $user->getDisplayName() : '';
    }
}
