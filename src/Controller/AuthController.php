<?php

namespace App\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserProviderInterface $userProvider,
        UserPasswordHasherInterface $passwordEncoder,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        // Récupère les données de la requête (username et password)
        $data = json_decode($request->getContent(), true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        // Recherche l'utilisateur par son identifiant (nom d'utilisateur ou email)
        $user = $userProvider->loadUserByIdentifier($username);

        // Vérifie si l'utilisateur existe et si le mot de passe est correct
        if (!$user || !$passwordEncoder->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        // Vérifie si l'utilisateur a le rôle "ROLE_API"
        if (!in_array('ROLE_API', $user->getRoles(), true)) {
            return new JsonResponse(['error' => 'Access denied. You do not have the required ROLE_API.'], 403);
        }

        // Génère le token JWT pour l'utilisateur authentifié
        $token = $jwtManager->create($user);

        // Retourne le token dans la réponse JSON
        return new JsonResponse(['token' => $token]);
    }
}
