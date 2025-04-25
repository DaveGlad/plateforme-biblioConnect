<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testLoginPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'Connexion');
    }

    public function testLoginWithBadCredentials(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form([
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $client->submit($form);
        $client->followRedirect();

        $this->assertSelectorExists('.alert-danger');
    }

    public function testSuccessfulLogin(): void
    {
        $client = static::createClient();
        
        // Vérifier si l'utilisateur de test existe
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail('user@example.com');
        
        if (!$testUser) {
            $this->markTestSkipped('L\'utilisateur de test n\'existe pas. Veuillez charger les fixtures d\'abord.');
        }
        
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form([
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $client->submit($form);
        
        $this->assertResponseRedirects();
        $client->followRedirect();
        
        // Vérifier que l'utilisateur est bien connecté
        $this->assertSelectorTextContains('.navbar-nav', $testUser->getFirstName());
    }

    public function testRegistrationPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'Créer un compte');
    }
}