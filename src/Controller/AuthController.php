<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuthController extends AbstractController
{
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    #[Route("/api/protected", name:"api_protected", methods:"GET")]
    public function protectedRoute(): JsonResponse
    {
        // Récupérer l'utilisateur authentifié
        $user = $this->tokenStorage->getToken()->getUser();

        // Vérifier si l'utilisateur est authentifié
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse(['message' => 'This is a protected route', 'user' => $user->getEmail()]);
    }
}
