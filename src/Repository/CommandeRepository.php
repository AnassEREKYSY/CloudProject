<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 *
 * @method Commande|null find($id, $lockMode = null, $lockVersion = null)
 * @method Commande|null findOneBy(array $criteria, array $orderBy = null)
 * @method Commande[]    findAll()
 * @method Commande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    public function isVehicleAvailable($idVehicule, \DateTime $dateDebut, \DateTime $dateFin): bool
    {
        $queryBuilder = $this->createQueryBuilder('c');

        $queryBuilder
            ->andWhere('c.id_vehicule = :id_vehicule')
            ->setParameter('id_vehicule', $idVehicule)
            ->andWhere('c.dateTime_heure_fin > :dateDebut')
            ->andWhere('c.date_heure_depart < :dateFin')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin);

        $conflictingCommands = $queryBuilder->getQuery()->getResult();

        return empty($conflictingCommands);
    }


public function findReservedVehiculeIds(\DateTime $dateDebut, \DateTime $dateFin): array
{
    $qb = $this->createQueryBuilder('c')
        ->select('v.id')
        ->join('c.id_vehicule', 'v') 
        ->where('c.date_heure_depart <= :dateFin')
        ->andWhere('c.dateTime_heure_fin >= :dateDebut')
        ->setParameter('dateDebut', $dateDebut)
        ->setParameter('dateFin', $dateFin);

    $results = $qb->getQuery()->getResult();

    $reservedVehiculeIds = array_map(function ($result) {
        return $result['id'];
    }, $results);

    return $reservedVehiculeIds;
}

//    /**
//     * @return Commande[] Returns an array of Commande objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Commande
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
