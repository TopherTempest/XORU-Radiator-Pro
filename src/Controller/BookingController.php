<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Repository\AvailabilityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ServiceRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class BookingController extends AbstractController
{
    #[Route('/booking', name: 'booking_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        CustomerRepository $customers,
        ServiceRepository $services,
        AvailabilityRepository $availability,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokens,
    ): Response {
        if (!$csrfTokens->isTokenValid(new CsrfToken('booking', $request->request->getString('_token')))) {
            throw $this->createAccessDeniedException('Invalid request token.');
        }

        if (!$request->getSession()->has('customer_id')) {
            $this->addFlash('error', 'Sign in to submit a booking or estimate.');
            return $this->redirectToRoute('customer_login');
        }

        $type = $request->request->getString('form_type', 'booking');
        $dashboardTab = $type === 'estimate' ? 'estimate' : 'booking';
        $name = trim($request->request->getString('name'));
        $phone = trim($request->request->getString('phone'));
        $vehicle = trim($request->request->getString('vehicle'));
        $message = trim($request->request->getString('message'));
        $serviceId = filter_var($request->request->get('service_id'), FILTER_VALIDATE_INT);
        $availabilityId = filter_var($request->request->get('availability_id'), FILTER_VALIDATE_INT);
        $date = trim($request->request->getString('date'));
        $time = trim($request->request->getString('time'));

        if (!in_array($type, ['booking', 'estimate'], true)
            || $name === ''
            || !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)
            || $vehicle === ''
            || $message === ''
            || $serviceId === false
            || $serviceId < 1) {
            $this->addFlash('error', 'Please complete all fields with valid contact details.');
            return $this->redirectToRoute('customer_dashboard', ['tab' => $dashboardTab]);
        }

        $service = $services->find($serviceId);
        if ($service === null) {
            $this->addFlash('error', 'Select a valid service.');
            return $this->redirectToRoute('customer_dashboard', ['tab' => $dashboardTab]);
        }

        $customer = $customers->find((int) $request->getSession()->get('customer_id'));
        if ($customer === null) {
            throw $this->createAccessDeniedException('Customer account no longer exists.');
        }

        $scheduledDate = null;
        $scheduledTime = null;
        if ($type === 'booking') {
            $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            $parsedTime = DateTimeImmutable::createFromFormat('!H:i', $time);
            if ($availabilityId === false || $availabilityId < 1
                || !$parsedDate || $parsedDate->format('Y-m-d') !== $date
                || !$parsedTime || $parsedTime->format('H:i') !== $time
                || $date < date('Y-m-d')) {
                $this->addFlash('error', 'Choose a valid future schedule.');
                return $this->redirectToRoute('customer_dashboard', ['tab' => $dashboardTab]);
            }
            $scheduledDate = $parsedDate;
            $scheduledTime = $parsedTime;
        }

        try {
            $entityManager->wrapInTransaction(function () use (
                $entityManager,
                $availability,
                $customer,
                $service,
                $type,
                $name,
                $phone,
                $vehicle,
                $message,
                $availabilityId,
                $date,
                $time,
                $scheduledDate,
                $scheduledTime,
            ): void {
                $slot = null;
                if ($type === 'booking') {
                    $slot = $availability->findLockedAvailable($availabilityId);
                    if ($slot === null
                        || $slot->getAvailableDate()->format('Y-m-d') !== $date
                        || $slot->getAvailableTime()->format('H:i') !== $time) {
                        throw new \RuntimeException('That schedule is no longer available. Please choose another slot.');
                    }
                    $slot->setStatus('Booked');
                }

                $entityManager->persist(new Booking(
                    $customer,
                    $type,
                    $name,
                    $phone,
                    $vehicle,
                    $service,
                    $slot,
                    $scheduledDate,
                    $scheduledTime,
                    $message,
                ));
            });
        } catch (\Throwable $exception) {
            $this->addFlash('error', $exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'Your request could not be saved. Please try again.');
            return $this->redirectToRoute('customer_dashboard', ['tab' => $dashboardTab]);
        }

        $this->addFlash('success', $type === 'booking' ? 'Booking submitted successfully.' : 'Estimate request submitted.');
        return $this->redirectToRoute('customer_dashboard', ['tab' => 'bookings']);
    }
}
