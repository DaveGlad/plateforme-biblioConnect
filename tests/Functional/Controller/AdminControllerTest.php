<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminControllerTest extends WebTestCase
{
    public function testAdminDashboardRequiresAdminRole(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        
        // Trouver un utilisateur standard (non-admin)
        $user = $userRepository->findOneByEmail('user@example.com');
        
        if (!$user) {
            $this->markTestSkipped('Utilisateur standard non trouvé pour le test.');
        }
        
        // Se connecter en tant qu'utilisateur standard
        $client->loginUser($user);
        
        // Tenter d'accéder au tableau de bord admin
        $client->request('GET', '/admin/');
        
        // Vérifier que l'accès est refusé
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminDashboardAccessibleForAdmin(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        
        // Trouver un utilisateur admin
        $admin = $userRepository->findOneByEmail('admin@example.com');
        
        if (!$admin) {
            $this->markTestSkipped('Utilisateur admin non trouvé pour le test.');
        }
        
        // Se connecter en tant qu'admin
        $client->loginUser($admin);
        
        // Accéder au tableau de bord admin
        $client->request('GET', '/admin/');
        
        // Vérifier que l'accès est autorisé
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Tableau de bord administrateur');
    }

    public function testAdminBookCreation(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        
        // Trouver un utilisateur admin
        $admin = $userRepository->findOneByEmail('admin@example.com');
        
        if (!$admin) {
            $this->markTestSkipped('Utilisateur admin non trouvé pour le test.');
        }
        
        // Se connecter en tant qu'admin
        $client->loginUser($admin);
        
        // Accéder au formulaire de création de livre
        $crawler = $client->request('GET', '/admin/books/new');
        
        // Vérifier que la page se charge correctement
        $this->assertResponseIsSuccessful();
        
        // Note: Compléter ce test pour soumettre le formulaire nécessiterait
        // d'avoir des données pour les entités liées (auteur, catégories, langue),
        // ce qui rendrait le test complexe. Pour un test complet, vous devriez
        // préparer ces données au préalable ou utiliser des mocks.
    }
}