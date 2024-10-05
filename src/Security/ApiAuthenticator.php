<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ApiAuthenticator extends AbstractAuthenticator
{
    private $jwtTokenManager;
    private $userProvider;

    public function __construct(JWTTokenManagerInterface $jwtTokenManager, UserProviderInterface $userProvider)
    {
        $this->jwtTokenManager = $jwtTokenManager;
        $this->userProvider = $userProvider;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $authorizationHeader = $request->headers->get('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);

        // Si vous souhaitez ajouter une logique de validation du token, c'est ici.
        $user = $this->jwtTokenManager->decode($token); // Décodez le token ici
        if (!$user) {
            throw new AuthenticationException('Token is invalid.');
        }
        
        // Utilisez l'email ou l'identifiant pour récupérer l'utilisateur
        $userBadge = new UserBadge($user['email']); // Supposons que l'email soit utilisé comme identifiant

        return new Passport($userBadge);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Vous pouvez gérer les actions à prendre après le succès de l'authentification ici
        return null; // Retourner null pour continuer le traitement normal
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response('Authentication Failed: ' . $exception->getMessage(), Response::HTTP_UNAUTHORIZED);
    }
}
