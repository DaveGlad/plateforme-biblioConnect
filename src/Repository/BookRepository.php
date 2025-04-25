<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 *
 * @method Book|null find($id, $lockMode = null, $lockVersion = null)
 * @method Book|null findOneBy(array $criteria, array $orderBy = null)
 * @method Book[]    findAll()
 * @method Book[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * Recherche des livres par titre, auteur et/ou catégorie
     */
    public function search(?string $query = null, ?object $author = null, ?object $category = null): array
    {
        $qb = $this->createQueryBuilder('b');
        
        // Jointures pour améliorer les performances
        $qb->leftJoin('b.author', 'a')
           ->leftJoin('b.categories', 'c')
           ->leftJoin('b.language', 'l');
           
        // Sélectionner toutes les données nécessaires en une seule requête
        $qb->addSelect('a', 'c', 'l');
        
        // Appliquer les filtres de recherche
        if ($query) {
            $qb->andWhere('b.title LIKE :query OR b.description LIKE :query OR b.isbn LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }
        
        if ($author) {
            $qb->andWhere('b.author = :author')
               ->setParameter('author', $author);
        }
        
        if ($category) {
            $qb->andWhere(':category MEMBER OF b.categories')
               ->setParameter('category', $category);
        }
        
        // Tri par défaut
        $qb->orderBy('b.title', 'ASC');
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Trouver les livres avec peu d'exemplaires disponibles (alerte de stock)
     */
    public function findLowStock(int $threshold = 2): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.availableCopies <= :threshold')
            ->andWhere('b.availableCopies > 0')
            ->setParameter('threshold', $threshold)
            ->orderBy('b.availableCopies', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les livres populaires basés sur le nombre de réservations
     */
    public function findPopularBooks(int $limit = 10): array
    {
        return $this->createQueryBuilder('b')
            ->select('b', 'COUNT(r.id) as reservations_count')
            ->leftJoin('b.reservations', 'r')
            ->groupBy('b.id')
            ->orderBy('reservations_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les livres bien notés
     */
    public function findHighlyRated(float $minRating = 4.0, int $limit = 10): array
    {
        $em = $this->getEntityManager();
        
        $query = $em->createQuery(
            'SELECT b, AVG(r.rating) as avg_rating
             FROM App\Entity\Book b
             JOIN b.reviews r
             WHERE r.status = :status
             GROUP BY b.id
             HAVING avg_rating >= :minRating
             ORDER BY avg_rating DESC'
        )
        ->setParameter('status', 'approved')
        ->setParameter('minRating', $minRating)
        ->setMaxResults($limit);
        
        return $query->getResult();
    }
}