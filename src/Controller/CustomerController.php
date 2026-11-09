<?php

namespace App\Controller;

use App\Security\XoruUser;
use App\Service\BookingTrashManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class CustomerController extends AbstractController
{
    #[Route('/customer/dashboard', name: 'customer_dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_CUSTOMER')]
    public function dashboard(Connection $connection): Response
    {
        $customerId = $this->customerId();
        $customer = $connection->fetchAssociative('SELECT id, name, email, provider FROM customers WHERE id = ?', [$customerId]);
        if (!$customer) {
            throw $this->createAccessDeniedException('Customer account no longer exists.');
        }

        return $this->render('customer/dashboard.html.twig', [
            'customer' => $customer,
            'services' => $connection->fetchAllAssociative('SELECT id, service_name FROM services ORDER BY id'),
            'slots' => $connection->fetchAllAssociative("SELECT id, available_date, available_time FROM availability WHERE status = 'Available' AND available_date >= CURDATE() ORDER BY available_date, available_time"),
            'bookings' => $connection->fetchAllAssociative('SELECT b.*, s.service_name FROM bookings b JOIN services s ON s.id = b.service_id WHERE b.customer_id = ? AND b.deleted_at IS NULL ORDER BY b.created_at DESC', [$customerId]),
            'reports' => $connection->fetchAllAssociative('SELECT id, type, rating, subject, message, status, admin_reply, created_at FROM reports WHERE customer_id = ? ORDER BY created_at DESC', [$customerId]),
        ]);
    }

    #[Route('/customer/booking/delete', name: 'customer_booking_delete', methods: ['POST'])]
    #[IsGranted('ROLE_CUSTOMER')]
    public function deleteBooking(
        Request $request,
        BookingTrashManager $trashManager,
        CsrfTokenManagerInterface $csrfTokens,
    ): Response {
        if (!$csrfTokens->isTokenValid(new CsrfToken('customer_delete_booking', $request->request->getString('_token')))) {
            throw $this->createAccessDeniedException('Invalid request token.');
        }

        $bookingId = filter_var($request->request->get('booking_id'), FILTER_VALIDATE_INT);
        if (!$bookingId || $bookingId < 1) {
            $this->addFlash('error', 'Select a valid booking to delete.');
            return $this->redirectToRoute('customer_dashboard', ['tab' => 'bookings']);
        }

        try {
            $trashManager->trash($bookingId, $this->customerId());
            $this->addFlash('success', 'Your request was moved to Trash. Contact an administrator if it needs to be restored.');
        } catch (\RuntimeException $exception) {
            $this->addFlash('error', $exception->getMessage());
        } catch (\Throwable) {
            $this->addFlash('error', 'Your request could not be moved to Trash.');
        }

        return $this->redirectToRoute('customer_dashboard', ['tab' => 'bookings']);
    }

    #[Route('/customer/report', name: 'customer_report_submit', methods: ['POST'])]
    #[IsGranted('ROLE_CUSTOMER')]
    public function submitReport(Request $request, Connection $connection, CsrfTokenManagerInterface $csrfTokens): Response
    {
        if (!$csrfTokens->isTokenValid(new CsrfToken('report', $request->request->getString('_token')))) {
            throw $this->createAccessDeniedException('Invalid request token.');
        }

        $type = $request->request->getString('type', 'Inconvenience');
        $subject = trim($request->request->getString('subject'));
        $message = trim($request->request->getString('message'));
        $ratingValue = $request->request->getString('rating');
        $rating = $ratingValue === '' ? null : filter_var($ratingValue, FILTER_VALIDATE_INT);

        if (!in_array($type, ['Inconvenience', 'Review'], true)
            || $subject === '' || strlen($subject) > 200
            || $message === '' || strlen($message) > 5000
            || ($type === 'Review' && ($rating === false || $rating < 1 || $rating > 5))) {
            $this->addFlash('error', 'Please check the report details and try again. Reviews need a rating from 1 to 5.');
            return $this->redirectToRoute('customer_dashboard', ['tab' => 'reports']);
        }

        $connection->insert('reports', [
            'customer_id' => $this->customerId(),
            'type' => $type,
            'rating' => $type === 'Review' ? $rating : null,
            'subject' => $subject,
            'message' => $message,
        ]);

        $this->addFlash('success', 'Your report or review was submitted successfully.');
        return $this->redirectToRoute('customer_dashboard', ['tab' => 'reports']);
    }

    #[Route('/customer/settings', name: 'customer_settings', methods: ['GET'])]
    #[IsGranted('ROLE_CUSTOMER')]
    public function settings(Connection $connection): Response
    {
        $customer = $connection->fetchAssociative(
            'SELECT name, email, provider FROM customers WHERE id = ?',
            [$this->customerId()]
        );

        return $this->render('customer/settings.html.twig', ['customer' => $customer]);
    }

    private function customerId(): int
    {
        $user = $this->getUser();
        if (!$user instanceof XoruUser) {
            throw $this->createAccessDeniedException();
        }

        return $user->getId();
    }
}