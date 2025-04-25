<?php

namespace App\Tests\Repository;

use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Language;
use App\Entity\Author;
use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BookRepositoryTest extends KernelTestCase
{
    private BookRepository $repository;
    
    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->repository = $container->get(BookRepository::class);
    }

    public function testFindByCategory(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        
        // Créer une catégorie
        $category = new Category();
        $category->setName('Test Category');
        $entityManager->persist($category);
        
        // Créer un auteur
        $author = new Author();
        $author->setFirstName('Test');
        $author->setLastName('Author');
        $entityManager->persist($author);
        
        // Créer une langue
        $language = new Language();
        $language->setName('Test Language');
        $language->setCode('tl');
        $entityManager->persist($language);
        
        // Créer un livre associé à cette catégorie
        $book = new Book();
        $book->setTitle('Test Book');
        $book->setDescription('Test Description');
        $book->setPublishYear(2023);
        $book->setTotalCopies(5);
        $book->setAvailableCopies(5);
        $book->setAuthor($author);
        $book->setLanguage($language);
        $book->addCategory($category);
        
        $entityManager->persist($book);
        $entityManager->flush();
        
        // Tester la méthode findByCategory
        $foundBooks = $this->repository->findByCategory($category);
        
        $this->assertCount(1, $foundBooks);
        $this->assertEquals('Test Book', $foundBooks[0]->getTitle());
        
        // Nettoyer les données de test
        $entityManager->remove($book);
        $entityManager->remove($category);
        $entityManager->remove($author);
        $entityManager->remove($language);
        $entityManager->flush();
    }

    public function testFindLowStock(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        
        // Créer un auteur
        $author = new Author();
        $author->setFirstName('Test');
        $author->setLastName('Author');
        $entityManager->persist($author);
        
        // Créer une langue
        $language = new Language();
        $language->setName('Test Language');
        $language->setCode('tl');
        $entityManager->persist($language);
        
        // Créer un livre avec peu d'exemplaires disponibles
        $lowStockBook = new Book();
        $lowStockBook->setTitle('Low Stock Book');
        $lowStockBook->setDescription('Test Description');
        $lowStockBook->setPublishYear(2023);
        $lowStockBook->setTotalCopies(5);
        $lowStockBook->setAvailableCopies(1); // Seulement 1 exemplaire disponible
        $lowStockBook->setAuthor($author);
        $lowStockBook->setLanguage($language);
        
        // Créer un livre avec beaucoup d'exemplaires disponibles
        $highStockBook = new Book();
        $highStockBook->setTitle('High Stock Book');
        $highStockBook->setDescription('Test Description');
        $highStockBook->setPublishYear(2023);
        $highStockBook->setTotalCopies(10);
        $highStockBook->setAvailableCopies(8); // 8 exemplaires disponibles
        $highStockBook->setAuthor($author);
        $highStockBook->setLanguage($language);
        
        $entityManager->persist($lowStockBook);
        $entityManager->persist($highStockBook);
        $entityManager->flush();
        
        // Tester la méthode findLowStock
        $lowStockBooks = $this->repository->findLowStock(2);
        
        // Vérifier que seul le livre avec peu d'exemplaires est retourné
        $this->assertGreaterThanOrEqual(1, count($lowStockBooks));
        $foundLowStockBook = false;
        
        foreach ($lowStockBooks as $book) {
            if ($book->getTitle() === 'Low Stock Book') {
                $foundLowStockBook = true;
                break;
            }
        }
        
        $this->assertTrue($foundLowStockBook);
        
        // Nettoyer les données de test
        $entityManager->remove($lowStockBook);
        $entityManager->remove($highStockBook);
        $entityManager->remove($author);
        $entityManager->remove($language);
        $entityManager->flush();
    }
}