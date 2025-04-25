<?php

namespace App\Tests\Entity;

use App\Entity\Book;
use App\Entity\Review;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ReviewTest extends TestCase
{
    private Review $review;
    private Book $book;
    private User $user;

    protected function setUp(): void
    {
        $this->book = new Book();
        $this->book->setTitle('Test Book');
        
        $this->user = new User();
        $this->user->setFirstName('Test');
        $this->user->setLastName('User');
        $this->user->setEmail('test@example.com');
        
        $this->review = new Review();
        $this->review->setBook($this->book);
        $this->review->setUser($this->user);
        $this->review->setRating(4);
        $this->review->setComment('This is a test comment');
    }

    public function testInitialStatus(): void
    {
        $this->assertEquals(Review::STATUS_PENDING, $this->review->getStatus());
    }

    public function testCreatedAtDate(): void
    {
        $this->assertInstanceOf(\DateTimeInterface::class, $this->review->getCreatedAt());
    }

    public function testReviewProperties(): void
    {
        $this->assertEquals(4, $this->review->getRating());
        $this->assertEquals('This is a test comment', $this->review->getComment());
        $this->assertSame($this->book, $this->review->getBook());
        $this->assertSame($this->user, $this->review->getUser());
    }

    public function testApprove(): void
    {
        $this->review->approve();
        $this->assertEquals(Review::STATUS_APPROVED, $this->review->getStatus());
    }

    public function testReject(): void
    {
        $this->review->reject();
        $this->assertEquals(Review::STATUS_REJECTED, $this->review->getStatus());
    }

    public function testIsPending(): void
    {
        $this->assertTrue($this->review->isPending());
        
        $this->review->approve();
        $this->assertFalse($this->review->isPending());
        
        $this->review->setStatus(Review::STATUS_PENDING);
        $this->assertTrue($this->review->isPending());
    }
}