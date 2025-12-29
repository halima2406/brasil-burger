<?php

namespace App\Repository;

use App\Entity\Burger;
use App\DTO\BurgerSearchFormDto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Burger>
 */
class BurgerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Burger::class);
    }

    public function findAvailable(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.disponible = :disponible')
            ->andWhere('b.archive = :archive')
            ->setParameter('disponible', true)
            ->setParameter('archive', false)
            ->orderBy('b.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche paginée avec filtres
     */
    public function searchPaginated(BurgerSearchFormDto $search, int $page = 1, int $limit = 7): array
    {
        $qb = $this->createQueryBuilder('b')
            ->orderBy('b.nom', 'ASC');

        // Filtrage par nom
        if (!empty($search->nom)) {
            $qb->andWhere('b.nom LIKE :nom')
               ->setParameter('nom', '%' . $search->nom . '%');
        }

        // Filtrage par statut
        if ($search->statut === 'disponible') {
            $qb->andWhere('b.disponible = :disponible')
               ->andWhere('b.archive = :archive')
               ->setParameter('disponible', true)
               ->setParameter('archive', false);
        } elseif ($search->statut === 'indisponible') {
            $qb->andWhere('b.disponible = :disponible')
               ->andWhere('b.archive = :archive')
               ->setParameter('disponible', false)
               ->setParameter('archive', false);
        } elseif ($search->statut === 'archive') {
            $qb->andWhere('b.archive = :archive')
               ->setParameter('archive', true);
        }

        // Filtrage par prix
        if ($search->prixMin !== null) {
            $qb->andWhere('b.prix >= :prixMin')
               ->setParameter('prixMin', $search->prixMin);
        }
        if ($search->prixMax !== null) {
            $qb->andWhere('b.prix <= :prixMax')
               ->setParameter('prixMax', $search->prixMax);
        }

        // Compter total
        $countQb = clone $qb;
        $countQb->select('COUNT(b.id)');
        $total = $countQb->getQuery()->getSingleScalarResult();

        // Pagination
        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();

        return [$results, $total];
    }

    /**
     * Trouver les burgers les plus vendus (populaires) d'une date donnée
     */
    public function findBurgersPopulaires(\DateTime $date, int $limit = 5): array
    {
        return $this->createQueryBuilder('b')
            ->select('b, COUNT(c.id) as nbVentes')
            ->leftJoin('b.commandes', 'c')
            ->where('DATE(c.dateCommande) = DATE(:date)')
            ->andWhere('c.etat = :etat')
            ->andWhere('b.archive = :archive')
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('etat', 'validee')
            ->setParameter('archive', false)
            ->groupBy('b.id')
            ->orderBy('nbVentes', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des burgers
     */
    public function getStatsBurgers(): array
    {
        $qb = $this->createQueryBuilder('b');
        
        return [
            'total' => $qb->select('COUNT(b.id)')
                ->where('b.archive = :archive')
                ->setParameter('archive', false)
                ->getQuery()
                ->getSingleScalarResult(),
            
            'disponibles' => $qb->select('COUNT(b.id)')
                ->where('b.disponible = :disponible')
                ->andWhere('b.archive = :archive')
                ->setParameter('disponible', true)
                ->setParameter('archive', false)
                ->getQuery()
                ->getSingleScalarResult(),
            
            'indisponibles' => $qb->select('COUNT(b.id)')
                ->where('b.disponible = :disponible')
                ->andWhere('b.archive = :archive')
                ->setParameter('disponible', false)
                ->setParameter('archive', false)
                ->getQuery()
                ->getSingleScalarResult(),
            
            'archives' => $qb->select('COUNT(b.id)')
                ->where('b.archive = :archive')
                ->setParameter('archive', true)
                ->getQuery()
                ->getSingleScalarResult(),
        ];
    }

    public function save(Burger $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Burger $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
