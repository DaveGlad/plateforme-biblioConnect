<?php

namespace App\Tests\Controller;

use App\Repository\BookRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookControllerTest extends WebTestCase
{
    public function testBookCatalogPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/books/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Catalogue des livres');
    }

    public function testBookDetailPage(): void
    {
        $client = static::createClient();
        $bookRepository = static::getContainer()->get(BookRepository::class);
        
        // Vérifier si un livre existe dans la base de données
        $book = $bookRepository->findOneBy([]);
        
        if (!$book) {
            $this->markTestSkipped('Aucun livre trouvé dans la base de données pour le test.');
        }
        
        $client->request('GET', '/books/' . $book->getId());
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', $book->getTitle());
    }

    public function testBookReservationRequiresLogin(): void
    {
        $client = static::createClient();
        $bookRepository = static::getContainer()->get(BookRepository::class);
        
        // Trouver un livre disponible
        $book = $bookRepository->findOneBy(['availableCopies' => ['>', 0]]);
        
        if (!$book) {
            $this->markTestSkipped('Aucun livre disponible trouvé pour le test.');
        }
        
        $client->request('GET', '/books/' . $book->getId() . '/reserve');
        
        // Vérifier que la redirection vers la page de connexion se produit
        $this->assertResponseRedirects('/login');
    }

    public function testBookReservationByAuthenticatedUser(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $bookRepository = static::getContainer()->get(BookRepository::class);
        
        // Trouver un utilisateur et un livre disponible
        $user = $userRepository->findOneByEmail('user@example.com');
        $book = $bookRepository->findOneBy(['availableCopies' => ['>', 0]]);
        
        if (!$user || !$book) {
            $this->markTestSkipped('Utilisateur ou livre non trouvé pour le test.');
        }
        
        // Stocker le nombre d'exemplaires disponibles avant la réservation
        $beforeAvailableCopies = $book->getAvailableCopies();
        
        // Se connecter en tant qu'utilisateur
        $client->loginUser($user);
        
        // Réserver le livre
        $client->request('GET', '/books/' . $book->getId() . '/reserve');
        
        // Vérifier la redirection
        $this->assertResponseRedirects('/user/reservations');
        $client->followRedirect();
        
        // Vérifier que la réservation a été créée
        $this->assertSelectorExists('.alert-success');
        
        // Recharger le livre depuis la base de données
        $updatedBook = $bookRepository->find($book->getId());
        
        // Vérifier que le nombre d'exemplaires disponibles a diminué de 1
        $this->assertEquals($beforeAvailableCopies - 1, $updatedBook->getAvailableCopies());
    }
}