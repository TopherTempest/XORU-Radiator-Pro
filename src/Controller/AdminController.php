<?php

namespace App\Controller;

use App\Security\XoruUser;
use App\Service\BookingTrashManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
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
    public function dashboard(Connection $connection, Request $request): Response
    {
        $month = date('Y-m');
        $bookings = $connection->fetchAllAssociative(
            'SELECT b.*, s.service_name, c.name AS customer_name, c.email AS customer_email FROM bookings b JOIN services s ON s.id = b.service_id JOIN customers c ON c.id = b.customer_id WHERE b.deleted_at IS NULL ORDER BY b.created_at DESC'
        );
        $trashedBookings = $connection->fetchAllAssociative(
            'SELECT b.*, s.service_name, c.name AS customer_name, c.email AS customer_email FROM bookings b JOIN services s ON s.id = b.service_id JOIN customers c ON c.id = b.customer_id WHERE b.deleted_at IS NOT NULL ORDER BY b.deleted_at DESC'
        );

        return $this->render('admin/dashboard.html.twig', [
            'bookings' => $bookings,
            'trashedBookings' => $trashedBookings,
            'bookingView' => $request->query->get('tab') === 'trash' ? 'trash' : 'active',
            'slots' => $connection->fetchAllAssociative('SELECT id, available_date, available_time, status FROM availability WHERE available_date >= CURDATE() ORDER BY available_date, available_time'),
            'freeSlots' => $connection->fetchAllAssociative("SELECT id, available_date, available_time FROM availability WHERE status = 'Available' AND available_date >= CURDATE() ORDER BY available_date, available_time"),
            'subscribers' => $connection->fetchAllAssociative('SELECT id, email, created_at FROM subscribers ORDER BY created_at DESC'),
            'metrics' => [
                'monthBookings' => (int) $connection->fetchOne("SELECT COUNT(*) FROM bookings WHERE deleted_at IS NULL AND date LIKE ?", [$month.'%']),
                'pending' => (int) $connection->fetchOne("SELECT COUNT(*) FROM bookings WHERE deleted_at IS NULL AND date LIKE ? AND status = 'Pending'", [$month.'%']),
                'approved' => (int) $connection->fetchOne("SELECT COUNT(*) FROM bookings WHERE deleted_at IS NULL AND date LIKE ? AND status = 'Approved'", [$month.'%']),
                'customers' => (int) $connection->fetchOne('SELECT COUNT(*) FROM customers'),
            ],
        ]);
    }

    #[Route('/admin/customers', name: 'admin_customers', methods: ['GET'])]
    public function customers(Connection $connection): Response
    {
        return $this->render('admin/customers.html.twig', [
            'customers' => $connection->fetchAllAssociative('SELECT c.id, c.name, c.email, c.provider, c.created_at, COUNT(b.id) AS booking_count FROM customers c LEFT JOIN bookings b ON b.customer_id = c.id AND b.deleted_at IS NULL GROUP BY c.id, c.name, c.email, c.provider, c.created_at ORDER BY c.created_at DESC'),
        ]);
    }

    #[Route('/admin/reports', name: 'admin_reports', methods: ['GET'])]
    public function reports(Connection $connection): Response
    {
        return $this->render('admin/reports.html.twig', [
            'reports' => $connection->fetchAllAssociative('SELECT r.*, c.name AS customer_name, c.email AS customer_email FROM reports r JOIN customers c ON c.id = r.customer_id ORDER BY r.created_at DESC'),
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
        Connection $connection,
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
                    $connection->insert('admins', [
                        'username' => $username,
                        'password' => $passwordHasher->hashPassword($user, $password),
                    ]);
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
        Connection $connection,
        CsrfTokenManagerInterface $csrfTokens,
        BookingTrashManager $trashManager,
    ): Response
    {
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
                $connection->beginTransaction();
                $booking = $connection->fetchAssociative('SELECT availability_id FROM bookings WHERE id = ? AND deleted_at IS NULL FOR UPDATE', [$bookingId]);
                if (!$booking) {
                    throw new \RuntimeException('Booking not found.');
                }
                $connection->update('bookings', ['status' => $status], ['id' => $bookingId]);
                if ($booking['availability_id']) {
                    $slotStatus = in_array($status, ['Declined', 'Cancelled'], true) ? 'Available' : 'Booked';
                    $connection->update('availability', ['status' => $slotStatus], ['id' => $booking['availability_id']]);
                }
                $connection->commit();
                $this->addFlash('success', 'Booking status updated.');
            } elseif ($action === 'reschedule' && $bookingId && $bookingId > 0 && $slotId && $slotId > 0) {
                $connection->beginTransaction();
                $booking = $connection->fetchAssociative('SELECT availability_id FROM bookings WHERE id = ? AND deleted_at IS NULL FOR UPDATE', [$bookingId]);
                $slot = $connection->fetchAssociative("SELECT id, available_date, available_time FROM availability WHERE id = ? AND status = 'Available' FOR UPDATE", [$slotId]);
                if (!$booking || !$slot) {
                    throw new \RuntimeException('Booking or new schedule was not available.');
                }
                if ($booking['availability_id']) {
                    $connection->update('availability', ['status' => 'Available'], ['id' => $booking['availability_id']]);
                }
                $connection->update('availability', ['status' => 'Booked'], ['id' => $slotId]);
                $connection->update('bookings', [
                    'availability_id' => $slotId,
                    'date' => $slot['available_date'],
                    'time' => $slot['available_time'],
                    'status' => 'Pending',
                    'admin_note' => 'Booking rescheduled by admin. Awaiting customer confirmation.',
                ], ['id' => $bookingId]);
                $connection->commit();
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
                $parsedDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
                $parsedTime = \DateTimeImmutable::createFromFormat('!H:i', $time);
                if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date || !$parsedTime || $parsedTime->format('H:i') !== $time || $date < date('Y-m-d')) {
                    throw new \RuntimeException('Enter a valid future date and time.');
                }
                $connection->insert('availability', ['available_date' => $date, 'available_time' => $time.':00', 'status' => 'Available']);
                $this->addFlash('success', 'New available schedule added.');
            } elseif (in_array($action, ['block_slot', 'unblock_slot'], true) && $slotId && $slotId > 0) {
                $oldStatus = $action === 'block_slot' ? 'Available' : 'Blocked';
                $newStatus = $action === 'block_slot' ? 'Blocked' : 'Available';
                $connection->executeStatement('UPDATE availability SET status = ? WHERE id = ? AND status = ?', [$newStatus, $slotId, $oldStatus]);
                $this->addFlash('success', $action === 'block_slot' ? 'Schedule slot blocked.' : 'Schedule slot made available.');
            } else {
                throw new \RuntimeException('Invalid admin action.');
            }
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $this->addFlash('error', $exception instanceof UniqueConstraintViolationException
                ? 'That date and time already exists.'
                : $exception->getMessage());
        }

        return $this->redirectToRoute('admin_dashboard', ['tab' => $bookingView]);
    }

    #[Route('/admin/report-action', name: 'admin_report_action', methods: ['POST'])]
    public function reportAction(Request $request, Connection $connection, CsrfTokenManagerInterface $csrfTokens): Response
    {
        if (!$csrfTokens->isTokenValid(new CsrfToken('admin_report', $request->request->getString('_token')))) {
            throw $this->createAccessDeniedException('Invalid request token.');
        }

        $reportId = filter_var($request->request->get('report_id'), FILTER_VALIDATE_INT);
        $status = $request->request->getString('status', 'New');
        $reply = trim($request->request->getString('admin_reply'));
        if (!$reportId || $reportId < 1 || !in_array($status, ['New', 'Read', 'Resolved'], true)) {
            $this->addFlash('error', 'Invalid report update.');
        } else {
            $connection->update('reports', ['status' => $status, 'admin_reply' => $reply !== '' ? $reply : null], ['id' => $reportId]);
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