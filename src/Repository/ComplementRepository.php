<?php
namespace App\Repository;
use App\Entity\Complement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class ComplementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Complement::class);
    }
}
