<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Liste de produits avec leurs détails
        $productsData = [
            [
                'name' => "Kit d'hygiène recyclable ",
                'price' => 24.99,
                'description' => "Pour une salle de bain éco-friendly",
                'photo' => '0f07c28090abf9ac0d263bf4473ba9a6.jpeg'
            ],
            [
                'name' => "Shot Tropical",
                'price' => 4.50,
                'description' => "Fruits frais, pressés à froid",
                'photo' => '14b95ab56656af06d7a69ab2d9ee44d0.jpeg'
            ],
            [
                'name' => "Gourde en bois",
                'price' => 16.90,
                'description' => "50cl, bois d’olivier",
                'photo' => '5c542819963e653209f118071a79567b.jpeg'
            ],
            [
                'name' => "Disques Démaquillants x3",
                'price' => 19.90,
                'description' => "Solution efficace pour vous démaquiller en douceur ",
                'photo' => '83102a01875727a5366e6a6fa9a75445.jpeg'
            ],
            [
                'name' => "Bougie Lavande & Patchouli",
                'price' => 32,
                'description' => "Cire naturelle",
                'photo' => 'c4700f712d7bef2fade2b494d4d2cd98.jpeg'
            ],
            [
                'name' => "Brosse à dent",
                'price' => 5.40,
                'description' => "Bois de hêtre rouge issu de forêts gérées durablement",
                'photo' => 'edebd52c007e82d992ba79ed0df88597.jpeg'
            ],
        ];

        // Boucle pour ajouter chaque produit dans la base de données
        foreach ($productsData as $productData) {
            $product = new Product();
            $product->setName($productData['name']);
            $product->setPrice($productData['price']);
            $product->setDescription($productData['description']);
            $product->setPhoto($productData['photo']);

            $manager->persist($product);
        }

        // Sauvegarde des produits en base de données
        $manager->flush();
    }
}
