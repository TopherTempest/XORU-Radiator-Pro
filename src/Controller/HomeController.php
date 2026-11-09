<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(Connection $connection, Request $request): Response
    {
        return $this->render('home/index.html.twig', [
            'services' => $connection->fetchAllAssociative('SELECT id, service_name FROM services ORDER BY id'),
            'availableSlots' => $connection->fetchAllAssociative("SELECT id, available_date, available_time FROM availability WHERE status = 'Available' AND available_date >= CURDATE() ORDER BY available_date, available_time"),
            'isCustomerLoggedIn' => $request->getSession()->has('customer_id'),
            'subscribeMessage' => $request->getSession()->getFlashBag()->get('subscribe_message')[0] ?? $request->getSession()->getFlashBag()->get('subscribe_error')[0] ?? '',
        ]);
    }

    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }

        if ($this->isGranted('ROLE_CUSTOMER')) {
            return $this->redirectToRoute('customer_dashboard');
        }

        return $this->redirectToRoute('customer_login');
    }
}