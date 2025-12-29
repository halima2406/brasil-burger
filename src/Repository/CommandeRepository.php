<?php

namespace App\Repository;

use App\Entity\Commande;
use App\Entity\Client;
use App\Entity\Livreur;
use App\Entity\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    public function findAllWithRelations(): array
    {
        // Récupérer toutes les commandes
        $commandes = $this->findAll();
        
        // Charger manuellement les relations pour éviter les problèmes de mapping
        foreach ($commandes as $commande) {
            try {
                // Charger le client
                if ($commande->getClientId()) {
                    $client = $this->getEntityManager()
                        ->getRepository(Client::class)
                        ->find($commande->getClientId());
                    $commande->setClient($client);
                }
                
                // Charger le livreur
                if ($commande->getLivreurId()) {
                    $livreur = $this->getEntityManager()
                        ->getRepository(Livreur::class)
                        ->find($commande->getLivreurId());
                    $commande->setLivreur($livreur);
                }
                
                // Charger la zone
                if ($commande->getZoneId()) {
                    $zone = $this->getEntityManager()
                        ->getRepository(Zone::class)
                        ->find($commande->getZoneId());
                    $commande->setZone($zone);
                }
            } catch (\Exception $e) {
                // Ignore les erreurs de relation et continue
                continue;
            }
        }
        
        return $commandes;
    }

    public function searchPaginated($dto, $page, $limit): array
    {
        try {
            $commandes = $this->findAllWithRelations();
            $count = count($commandes);
            
            // Pagination simple
            $offset = ($page - 1) * $limit;
            $paginatedCommandes = array_slice($commandes, $offset, $limit);
            
            return [$paginatedCommandes, $count];
        } catch (\Exception $e) {
            // En cas d'erreur, retourner un tableau vide
            return [[], 0];
        }
    }

    public function countByStatus(string $status): int
    {
        try {
            return $this->count(['statut' => $status]);
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function save(Commande $commande, bool $flush = false): void
    {
        $this->getEntityManager()->persist($commande);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Commande $commande, bool $flush = false): void
    {
        $this->getEntityManager()->remove($commande);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
