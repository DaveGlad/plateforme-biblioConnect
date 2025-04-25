<?php
namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
        $this->user->setFirstName('John');
        $this->user->setLastName('Doe');
        $this->user->setEmail('john.doe@example.com');
        $this->user->setPassword('password');
    }

    public function testGetFullName(): void
    {
        $this->assertEquals('John Doe', $this->user->getFullName());
    }

    public function testDefaultRole(): void
    {
        // Par défaut, tous les utilisateurs doivent avoir ROLE_USER
        $this->assertContains('ROLE_USER', $this->user->getRoles());
    }

    public function testSetRoles(): void
    {
        $this->user->setRoles(['ROLE_ADMIN']);
        
        // Même après avoir défini ROLE_ADMIN, l'utilisateur doit toujours avoir ROLE_USER
        $this->assertContains('ROLE_USER', $this->user->getRoles());
        $this->assertContains('ROLE_ADMIN', $this->user->getRoles());
    }

    public function testIsAdminWithAdminRole(): void
    {
        $this->user->setRoles(['ROLE_ADMIN']);
        $this->assertTrue($this->user->isAdmin());
    }

    public function testIsAdminWithoutAdminRole(): void
    {
        $this->assertFalse($this->user->isAdmin());
    }

    public function testIsLibrarianWithLibrarianRole(): void
    {
        $this->user->setRoles(['ROLE_LIBRARIAN']);
        $this->assertTrue($this->user->isLibrarian());
    }

    public function testIsLibrarianWithoutLibrarianRole(): void
    {
        $this->assertFalse($this->user->isLibrarian());
    }
}