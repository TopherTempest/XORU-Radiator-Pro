<?php

namespace App\Controller;

use App\Repository\AvailabilityRepository;
use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(
        ServiceRepository $services,
        AvailabilityRepository $availability,
        Request $request,
    ): Response {
        return $this->render('home/index.html.twig', [
            'services' => $services->findOptions(),
            'availableSlots' => $availability->findUpcomingRows(availableOnly: true),
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