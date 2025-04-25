<?php

namespace App\Tests\Entity;

use App\Entity\Book;
use App\Entity\Reservation;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ReservationTest extends TestCase
{
    private Reservation $reservation;
    private Book $book;
    private User $user;

    protected function setUp(): void
    {
        $this->book = new Book();
        $this->book->setTitle('Test Book');
        $this->book->setTotalCopies(5);
        $this->book->setAvailableCopies(3);
        
        $this->user = new User();
        $this->user->setFirstName('Test');
        $this->user->setLastName('User');
        $this->user->setEmail('test@example.com');
        
        $this->reservation = new Reservation();
        $this->reservation->setBook($this->book);
        $this->reservation->setUser($this->user);
    }

    public function testInitialStatus(): void
    {
        $this->assertEquals(Reservation::STATUS_PENDING, $this->reservation->getStatus());
    }

    public function testReservationDate(): void
    {
        $this->assertInstanceOf(\DateTimeInterface::class, $this->reservation->getReservationDate());
    }

    public function testActivate(): void
    {
        $this->reservation->activate();
        
        $this->assertEquals(Reservation::STATUS_ACTIVE, $this->reservation->getStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->reservation->getDueDate());
        
        // La date d'échéance devrait être dans environ 14 jours
        $expected = new \DateTime();
        $expected->modify('+14 days');
        
        $diff = $expected->diff($this->reservation->getDueDate())->days;
        $this->assertLessThanOrEqual(1, $diff); // Tolérance d'un jour pour les tests
    }

    public function testMarkAsReturned(): void
    {
        $this->reservation->markAsReturned();
        
        $this->assertEquals(Reservation::STATUS_RETURNED, $this->reservation->getStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->reservation->getReturnDate());
    }

    public function testCancel(): void
    {
        $this->reservation->cancel();
        
        $this->assertEquals(Reservation::STATUS_CANCELLED, $this->reservation->getStatus());
    }

    public function testIsOverdueWithActiveDueDate(): void
    {
        $this->reservation->activate();
        
        // Modifier la date d'échéance pour qu'elle soit dans le passé
        $dueDate = new \DateTime();
        $dueDate->modify('-1 day');
        $this->reservation->setDueDate($dueDate);
        
        $this->assertTrue($this->reservation->isOverdue());
    }

    public function testIsOverdueWithFutureDueDate(): void
    {
        $this->reservation->activate();
        
        // La date d'échéance par défaut est dans 14 jours (futur)
        $this->assertFalse($this->reservation->isOverdue());
    }
    
    public function testIsOverdueWithNonActiveStatus(): void
    {
        // Pour les réservations qui ne sont pas actives, isOverdue devrait toujours retourner false
        $this->reservation->setStatus(Reservation::STATUS_RETURNED);
        $dueDate = new \DateTime();
        $dueDate->modify('-1 day');
        $this->reservation->setDueDate($dueDate);
        
        $this->assertFalse($this->reservation->isOverdue());
    }
}