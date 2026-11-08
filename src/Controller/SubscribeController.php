<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class SubscribeController extends AbstractController
{
    #[Route('/subscribe', name: 'subscribe', methods: ['POST'])]
    public function subscribe(Request $request, Connection $connection, CsrfTokenManagerInterface $csrfTokens): Response
    {
        if (!$csrfTokens->isTokenValid(new CsrfToken('subscribe', $request->request->getString('_token')))) {
            throw $this->createAccessDeniedException('Invalid request token.');
        }

        $email = trim($request->request->getString('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('subscribe_error', 'Please enter a valid email address.');
            return $this->redirectToRoute('home', ['_fragment' => 'subscribe']);
        }

        try {
            $connection->insert('subscribers', ['email' => $email]);
            $this->addFlash('subscribe_message', 'Thanks! Your email has been added for maintenance tips.');
        } catch (UniqueConstraintViolationException) {
            $this->addFlash('subscribe_message', 'That email is already subscribed.');
        } catch (\Throwable) {
            $this->addFlash('subscribe_error', 'We could not save your subscription right now.');
        }

        return $this->redirectToRoute('home', ['_fragment' => 'subscribe']);
    }
}