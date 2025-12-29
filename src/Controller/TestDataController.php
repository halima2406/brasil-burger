<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Commande;
use App\Entity\Livreur;
use App\Entity\Zone;
use App\Service\GenerateNumeroService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/test')]
class TestDataController extends AbstractController
{
    #[Route('/create-sample-data', name: 'app_test_create_data')]
    public function createSampleData(
        EntityManagerInterface $em,
        GenerateNumeroService $numeroService
    ): Response {
        
        try {
            // Créer une zone de test
            $zone = new Zone();
            $zone->setNom('Plateau');
            $zone->setQuartiers('Centre-ville, Rebeuss');
            $zone->setPrixLivraison('500.00');
            $em->persist($zone);

            // Créer un livreur de test
            $livreur = new Livreur();
            $livreur->setNumero($numeroService->generateNumeroCommande()); // Réutilise le générateur
            $livreur->setNomComplet('Moussa Diallo');
            $livreur->setTelephone('771234567');
            $livreur->setEmail('moussa@brasilburger.sn');
            $livreur->setPassword('$2y$13$test'); // Password hashé factice
            $em->persist($livreur);

            // Créer un client de test
            $client = new Client();
            $client->setNumero($numeroService->generateNumeroClient());
            $client->setNomComplet('Aminata Ndiaye');
            $client->setPrenom('Aminata');
            $client->setTelephone('775678901');
            $client->setEmail('aminata@example.com');
            $client->setPassword('$2y$13$test'); // Password hashé factice
            $client->setAdresse('Plateau, Rue Carnot');
            $em->persist($client);

            // Créer des commandes de test
            for ($i = 1; $i <= 5; $i++) {
                $commande = new Commande();
                $commande->setNumero($numeroService->generateNumeroCommande());
                $commande->setClient($client);
                $commande->setZone($zone);
                $commande->setTypeConsommation($i % 3 == 0 ? 'livraison' : 'emporter');
                $commande->setQuantite(rand(1, 3));
                $commande->setMontantTotal((string) rand(2500, 8500));
                $commande->setEtat($i <= 2 ? 'en_cours' : ($i == 3 ? 'validee' : 'terminee'));
                
                if ($i == 3 && $commande->getTypeConsommation() === 'livraison') {
                    // Affecter le livreur à une commande pour test
                    $commande->setLivreur($livreur);
                }
                
                $em->persist($commande);
            }

            $em->flush();

            $this->addFlash('success', 'Données de test créées avec succès !');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la création des données : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_commande_list');
    }
}
