<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();
        return $this->render('home/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[IsGranted("ROLE_API")]
    #[Route('/api/products', name: 'app_products')]
    public function products(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();
        return $this->json( [
            'products' => $products,
        ]);
    }


    #[Route('/account/shopping-cart', name: 'app_shopping_cart')]
    public function shoppingCart(ProductRepository $productRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupération de l'utilisateur
        $user = $this->getUser();
    
        // Récupérer l'entité OrderRepository
        $orderRepository = $entityManager->getRepository(Order::class);
    
        // Chercher s'il existe une commande non validée pour cet utilisateur
        $orders = $orderRepository->findBy([
            'user' => $user,
            'validity' => 0
        ]);
    
        return $this->render('account/cart.html.twig', [
            'panier' => !empty($orders),
            'orders' => $orders
        ]);
    }
    
    #[Route('/account/shopping-cart/empty', name: 'app_empty_cart')]
    public function emptyCart(EntityManagerInterface $entityManager): Response
    {
        // Récupération de l'utilisateur
        $user = $this->getUser();
    
        // Récupérer l'entité OrderRepository
        $orderRepository = $entityManager->getRepository(Order::class);
    
        // Chercher toutes les commandes non validées pour cet utilisateur
        $orders = $orderRepository->findBy([
            'user' => $user,
            'validity' => 0
        ]);
    
        // Suppression de chaque commande
        foreach ($orders as $order) {
            $entityManager->remove($order);
        }
        $entityManager->flush();
    
        // Redirection vers la page du panier
        return $this->redirectToRoute('app_shopping_cart');
    }
    
    #[Route('/account/shopping-cart/validate', name: 'app_validate_cart')]
    public function validateCart(EntityManagerInterface $entityManager): Response
    {
        // Récupération de l'utilisateur
        $user = $this->getUser();
    
        // Récupérer l'entité OrderRepository
        $orderRepository = $entityManager->getRepository(Order::class);
    
        // Chercher toutes les commandes non validées pour cet utilisateur
        $orders = $orderRepository->findBy([
            'user' => $user,
            'validity' => 0
        ]);
    
        // Changer la validité de chaque commande à 1
        foreach ($orders as $order) {
            $order->setValidity(1);
            $entityManager->persist($order);
        }
        $entityManager->flush();
    
        // Redirection vers la page du panier
        return $this->redirectToRoute('app_shopping_cart');
    }
    

    #[IsGranted("ROLE_USER")]
    #[Route('/add-product/{id}', name:'add_product')]
    public function productOrder($id, ProductRepository $productRepository, EntityManagerInterface $entityManager ): Response
    {
        $user = $this->getUser();
        $product = $productRepository->find($id);
        $order = new  Order();
        $order->setProduct($product);
        $order->setUser($user);
        $order->setDate(date_create_immutable());
        $order->setQuantity(1);
        $order->setPrice($product->getPrice());
        $order->setValidity(0);

        $entityManager->persist($order);
        $entityManager->flush();

        return $this->redirectToRoute('app_product', ['id' => $product->getId()]);
    }

    #[IsGranted("ROLE_USER")]
    #[Route('/update-product/{id}', name:'update_product_plus')]
    public function updateOrder($id, OrderRepository $orderRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupération de l'utilisateur connecté
        $user = $this->getUser();
        
        // Récupération de la commande à partir de son ID
        $order = $orderRepository->find($id);
        
        // Vérification si la commande existe et si elle appartient bien à l'utilisateur connecté
        if (!$order || $order->getUser() !== $user) {
            return $this->redirectToRoute('app_home');
        }
    
        // Si l'utilisateur est bien celui qui a passé la commande, on peut procéder à la mise à jour de la quantité
        $quantity = $order->getQuantity();
        $newQuantity = $quantity + 1; // Augmentation de la quantité
        
        // Mise à jour des informations de la commande
        $order->setQuantity($newQuantity);
        $order->setPrice($order->getProduct()->getPrice() * $newQuantity); // Mise à jour du prix
        $order->setDate(date_create_immutable()); // Mise à jour de la date 
    
        // Sauvegarde des modifications dans la base de données
        $entityManager->persist($order);
        $entityManager->flush();
    
        // Redirection vers la page du produit
        return $this->redirectToRoute('app_product', ['id' => $order->getProduct()->getId()]);
    }
    
    #[Route('/update-product-less/{id}', name:'update_product_less')]
    public function lessOrder($id, OrderRepository $orderRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupération de l'utilisateur connecté
        $user = $this->getUser();
        
        // Récupération de la commande à partir de son ID
        $order = $orderRepository->find($id);
        
        // Vérification si la commande existe et si elle appartient bien à l'utilisateur connecté
        if (!$order || $order->getUser() !== $user) {
            return $this->redirectToRoute('app_home'); // ou afficher un message d'erreur personnalisé
        }
    
        // Récupération de l'ID du produit avant de potentiellement supprimer la commande
        $productId = $order->getProduct()->getId();
        
        // Si l'utilisateur est bien celui qui a passé la commande, on peut procéder à la mise à jour de la quantité
        $quantity = $order->getQuantity();
        if ($quantity > 1) {
            // Si la quantité est supérieure à 1, on la diminue
            $newQuantity = $quantity - 1;
    
            // Mise à jour des informations de la commande
            $order->setQuantity($newQuantity);
            $order->setPrice($order->getProduct()->getPrice() * $newQuantity); // Mise à jour du prix
            $order->setDate(date_create_immutable()); // Mise à jour de la date si nécessaire
    
            // Sauvegarde des modifications dans la base de données
            $entityManager->persist($order);
            $entityManager->flush();
        } else {
            // Si la quantité est de 1 ou moins, on supprime la commande
            $entityManager->remove($order);
            $entityManager->flush();
        }
    
        // Redirection vers la page du produit avec l'ID du produit sauvegardé
        return $this->redirectToRoute('app_product', ['id' => $productId]);
    }
    
    
    



    #[Route('/account', name: 'app_account')]
    public function account(ProductRepository $productRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupération de l'utilisateur
        $user = $this->getUser();

        if ($this->isGranted('ROLE_API') ) {
            $api = 1;
        }else(
            $api = 0 
        );

        // Récupérer l'entité OrderRepository
        $orderRepository = $entityManager->getRepository(Order::class);
    
        // Chercher s'il existe une commande non validée pour cet utilisateur
        $orders = $orderRepository->findBy([
            'user' => $user,
            'validity' => 1
        ]);
    
        return $this->render('account/account.html.twig', [
            'panier' => !empty($orders),
            'orders' => $orders,
            'api' => $api
        ]);
    }

    #[Route('/account/active-api/', name: 'active_api')]
    public function activeApi( EntityManagerInterface $em): Response
    {


        $user = $this->getUser() ;

        // Vérifiez si l'utilisateur existe
        if (!$user) {
            throw $this->createNotFoundException('Aucun utilisateur trouvé avec cet identifiant');
        }

        // Mettez à jour les rôles de l'utilisateur
        $user->setRoles(['ROLE_API']);

        $em->persist($user);
        $em->flush();

        // Redirigez vers le compte
        return $this->redirectToRoute('app_account');
    }

    #[Route('/account/disable-api/', name: 'disable_api')]
    public function disableApi( EntityManagerInterface $em): Response
    {


        $user = $this->getUser() ;

        // Vérifiez si l'utilisateur existe
        if (!$user) {
            throw $this->createNotFoundException('Aucun utilisateur trouvé avec cet identifiant');
        }

        // Mettez à jour les rôles de l'utilisateur
        $user->setRoles([]);

        $em->persist($user);
        $em->flush();

        // Redirigez vers le compte
        return $this->redirectToRoute('app_account');
    }

    #[Route('/product/{id}', name:'app_product')]
    public function product($id, ProductRepository $productRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupération de l'utilisateur
        $user = $this->getUser();
    
        // Récupération du produit
        $product = $productRepository->find($id);
    
        // Initialisation de la variable $panier
        $panier = 0;
    
        // Si l'utilisateur est connecté, on vérifie dans la table Order
        if ($user) {
            // Récupérer l'entité OrderRepository
            $orderRepository = $entityManager->getRepository(Order::class);
    
            // Chercher s'il existe une commande non validée pour cet utilisateur et ce produit
            $order = $orderRepository->findOneBy([
                'user' => $user,
                'product' => $product,
                'validity' => 0
            ]);
    
            // Si une commande non validée est trouvée, on met $panier à 1
            if ($order) {
                $panier = 1;
            }
        }
        if ($panier == 1) {
            return $this->render('home/product.html.twig', [
                'product' => $product,
                'panier' => $panier,
                'order' => $order
            ]);
        }else {
            return $this->render('home/product.html.twig', [
                'product' => $product,
                'panier' => $panier,
            ]);
        }


        
    }

    #[Route('/account/delete', name: 'account_delete', methods: ['POST'])]
    public function deleteAccount(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {

        // Récupération de l'utilisateur connecté
        $user = $this->getUser();
        if ($this->isCsrfTokenValid('account_delete', $request->request->get('_token'))) {
        // Suppression de l'utilisateur et de ses commandes
        $entityManager->remove($user);
        $entityManager->flush();
        }
        // Redirection vers la page d'accueil après suppression
        return $this->redirectToRoute('app_home');
    }

    
    
}
