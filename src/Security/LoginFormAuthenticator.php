<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class LoginFormAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function supports(Request $request): ?bool
    {
        return $request->isMethod('POST') && $request->getPathInfo() === '/customer/login';
    }

    public function authenticate(Request $request): Passport
    {
        $login = trim($request->request->getString('login'));
        $password = $request->request->getString('password');

        return new Passport(
            new UserBadge('login:'.$login),
            new PasswordCredentials($password),
            [new CsrfTokenBadge('authenticate', $request->request->getString('_token'))]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        $user = $token->getUser();
        if ($user instanceof XoruUser) {
            if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                $request->getSession()->set('admin_id', $user->getId());
                $request->getSession()->set('admin_username', $user->getDisplayName());
                return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
            }

            $request->getSession()->set('customer_id', $user->getId());
            $request->getSession()->set('customer_name', $user->getDisplayName());
            return new RedirectResponse($this->urlGenerator->generate('customer_dashboard'));
        }

        return new RedirectResponse($this->urlGenerator->generate('home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse
    {
        $request->getSession()->getFlashBag()->add('error', 'Invalid email/username or password.');
        return new RedirectResponse($this->urlGenerator->generate('customer_login'));
    }

    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('customer_login'));
    }
}