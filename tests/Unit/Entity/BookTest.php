<?php
namespace App\Tests\Entity;

use App\Entity\Book;
use App\Entity\Author;
use App\Entity\Category;
use App\Entity\Language;
use App\Entity\Review;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class BookTest extends TestCase
{
    private Book $book;
    private Author $author;
    private Language $language;

    protected function setUp(): void
    {
        $this->author = new Author();
        $this->author->setFirstName('Victor');
        $this->author->setLastName('Hugo');

        $this->language = new Language();
        $this->language->setName('Français');
        $this->language->setCode('fr');

        $this->book = new Book();
        $this->book->setTitle('Les Misérables');
        $this->book->setDescription('Un roman historique...');
        $this->book->setIsbn('9781234567897');
        $this->book->setPublishYear(1862);
        $this->book->setTotalCopies(10);
        $this->book->setAvailableCopies(5);
        $this->book->setAuthor($this->author);
        $this->book->setLanguage($this->language);
    }

    public function testBookBasicProperties(): void
    {
        $this->assertEquals('Les Misérables', $this->book->getTitle());
        $this->assertEquals('Un roman historique...', $this->book->getDescription());
        $this->assertEquals('9781234567897', $this->book->getIsbn());
        $this->assertEquals(1862, $this->book->getPublishYear());
        $this->assertEquals(10, $this->book->getTotalCopies());
        $this->assertEquals(5, $this->book->getAvailableCopies());
    }

    public function testBookRelationships(): void
    {
        $this->assertSame($this->author, $this->book->getAuthor());
        $this->assertSame($this->language, $this->book->getLanguage());
    }

    public function testBookCategories(): void
    {
        $category1 = new Category();
        $category1->setName('Roman');
        
        $category2 = new Category();
        $category2->setName('Classique');
        
        $this->book->addCategory($category1);
        $this->book->addCategory($category2);
        
        $this->assertCount(2, $this->book->getCategories());
        $this->assertTrue($this->book->getCategories()->contains($category1));
        $this->assertTrue($this->book->getCategories()->contains($category2));
        
        $this->book->removeCategory($category1);
        
        $this->assertCount(1, $this->book->getCategories());
        $this->assertFalse($this->book->getCategories()->contains($category1));
    }

    public function testIsAvailableWhenCopiesExist(): void
    {
        $this->book->setAvailableCopies(5);
        $this->assertTrue($this->book->isAvailable());
    }

    public function testIsAvailableWhenNoCopiesExist(): void
    {
        $this->book->setAvailableCopies(0);
        $this->assertFalse($this->book->isAvailable());
    }

    public function testGetAverageRatingWithNoReviews(): void
    {
        $this->assertNull($this->book->getAverageRating());
    }

    public function testGetAverageRatingWithReviews(): void
    {
        $user = new User();
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setEmail('test@example.com');
        
        $review1 = new Review();
        $review1->setRating(4);
        $review1->setBook($this->book);
        $review1->setUser($user);
        
        $review2 = new Review();
        $review2->setRating(2);
        $review2->setBook($this->book);
        $review2->setUser($user);
        
        // La méthode addReview met à jour la collection reviews
        $this->book->addReview($review1);
        $this->book->addReview($review2);
        
        // La moyenne devrait être (4 + 2) / 2 = 3
        $this->assertEquals(3, $this->book->getAverageRating());
    }
}