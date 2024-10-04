<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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


    #[Route('/account/shopping-cart', name: 'app_shopping_cart')]
    public function shoppingCart( ProductRepository $productRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupération de l'utilisateur
        $user = $this->getUser();
    

    
        // Initialisation de la variable $panier
        $panier = 0;

        // Récupérer l'entité OrderRepository
        $orderRepository = $entityManager->getRepository(Order::class);

        // Chercher s'il existe une commande non validée pour cet utilisateur et ce produit
        $orders = $orderRepository->findBy([
            'user' => $user,
            'validity' => 0
        ]);

        // Si une commande non validée est trouvée, on met $panier à 1
        if ($orders) {
            $panier = 1;
        }
        if ($panier == 1) {
            return $this->render('account/cart.html.twig', [
                'panier' => $panier,
                'orders' => $orders
            ]);
        }else {
            return $this->render('account/cart.html.twig', [
                'panier' => $panier,
            ]);
        }


        
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
    public function account(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
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
    
}
