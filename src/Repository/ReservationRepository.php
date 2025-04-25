<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 *
 * @method Reservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reservation[]    findAll()
 * @method Reservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Trouver les réservations en retard (échéance dépassée)
     */
    public function findOverdueReservations(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->andWhere('r.dueDate < :now')
            ->setParameter('status', Reservation::STATUS_ACTIVE)
            ->setParameter('now', $now)
            ->orderBy('r.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les réservations actives d'un utilisateur
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Reservation::STATUS_ACTIVE)
            ->orderBy('r.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver l'historique complet des réservations d'un utilisateur
     */
    public function findHistoryByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.reservationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les statistiques de réservation (nombre par statut)
     */
    public function getReservationStats(): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.status, COUNT(r.id) as count')
            ->groupBy('r.status');
        
        $results = $qb->getQuery()->getResult();
        
        // Formater les résultats en tableau associatif
        $stats = [];
        foreach ($results as $result) {
            $stats[$result['status']] = $result['count'];
        }
        
        return $stats;
    }
}