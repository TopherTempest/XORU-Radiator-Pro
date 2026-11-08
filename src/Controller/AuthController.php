<?php

namespace App\Controller;

use App\Security\XoruUser;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class AuthController extends AbstractController
{
    #[Route('/customer/login', name: 'customer_login', methods: ['GET', 'POST'])]
    public function customerLogin(): Response
    {
        return $this->render('auth/customer_login.html.twig', ['error' => $this->consumeErrorFlash()]);
    }

    #[Route('/admin/login', name: 'admin_login', methods: ['GET', 'POST'])]
    public function adminLogin(): Response
    {
        return $this->render('auth/admin_login.html.twig', ['error' => $this->consumeErrorFlash()]);
    }

    #[Route('/customer/register', name: 'customer_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        Connection $connection,
        UserPasswordHasherInterface $passwordHasher,
        CsrfTokenManagerInterface $csrfTokens,
    ): Response {
        $error = '';
        if ($request->isMethod('POST')) {
            if (!$csrfTokens->isTokenValid(new CsrfToken('register', $request->request->getString('_token')))) {
                throw $this->createAccessDeniedException('Invalid request token.');
            }

            $name = trim($request->request->getString('name'));
            $email = trim($request->request->getString('email'));
            $password = $request->request->getString('password');
            $confirmation = $request->request->getString('confirm_password');

            if ($name === '' || strlen($name) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
                $error = 'Enter a valid name and email address.';
            } elseif (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters.';
            } elseif ($password !== $confirmation) {
                $error = 'Passwords do not match.';
            } else {
                $user = new XoruUser(0, 'customer:'.$email, '', $name, ['ROLE_CUSTOMER']);
                try {
                    $connection->insert('customers', [
                        'name' => $name,
                        'email' => $email,
                        'password' => $passwordHasher->hashPassword($user, $password),
                        'provider' => 'email',
                    ]);
                    $this->addFlash('success', 'Your account is ready. Sign in to continue.');
                    return $this->redirectToRoute('customer_login');
                } catch (UniqueConstraintViolationException) {
                    $error = 'An account with that email already exists.';
                }
            }
        }

        return $this->render('auth/register.html.twig', ['error' => $error]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET', 'POST'])]
    public function logout(): never
    {
        throw new \LogicException('The logout route is intercepted by Symfony Security.');
    }

    private function consumeErrorFlash(): string
    {
        return $this->container->get('request_stack')->getSession()->getFlashBag()->get('error')[0] ?? '';
    }
}