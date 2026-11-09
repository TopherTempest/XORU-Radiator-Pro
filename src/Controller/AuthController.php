<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Security\XoruUser;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
        return $this->redirectToRoute('customer_login');
    }

    #[Route('/customer/register', name: 'customer_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
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
                    $customer = new Customer($name, $email, provider: 'email');
                    $customer->setPassword($passwordHasher->hashPassword($user, $password));
                    $entityManager->persist($customer);
                    $entityManager->flush();
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