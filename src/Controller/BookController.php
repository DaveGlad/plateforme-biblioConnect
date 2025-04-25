<?php

namespace App\Controller;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Review;
use App\Form\BookSearchType;
use App\Form\ReviewType;
use App\Repository\BookRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/books')]
class BookController extends AbstractController
{
    #[Route('/', name: 'book_index')]
    public function index(BookRepository $bookRepository, Request $request): Response
    {
        // Formulaire de recherche
        $form = $this->createForm(BookSearchType::class);
        $form->handleRequest($request);
        
        $books = [];
        
        if ($form->isSubmitted() && $form->isValid()) {
            $searchData = $form->getData();
            $books = $bookRepository->search(
                $searchData['query'] ?? null,
                $searchData['author'] ?? null,
                $searchData['category'] ?? null
            );
        } else {
            // Afficher tous les livres par défaut
            $books = $bookRepository->findAll();
        }
        
        return $this->render('book/index.html.twig', [
            'books' => $books,
            'search_form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'book_show', methods: ['GET'])]
    public function show(Book $book, ReviewRepository $reviewRepository): Response
    {
        // Récupérer les critiques approuvées pour ce livre
        $reviews = $reviewRepository->findBy([
            'book' => $book,
            'status' => Review::STATUS_APPROVED
        ]);
        
        return $this->render('book/show.html.twig', [
            'book' => $book,
            'reviews' => $reviews,
        ]);
    }

    #[Route('/{id}/review', name: 'book_review')]
    #[IsGranted('ROLE_USER')]
    public function review(Book $book, Request $request, EntityManagerInterface $entityManager): Response
    {
        $review = new Review();
        $review->setBook($book);
        $review->setUser($this->getUser());
        
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($review);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre critique a été envoyée et est en attente de modération.');
            
            return $this->redirectToRoute('book_show', ['id' => $book->getId()]);
        }
        
        return $this->render('book/review.html.twig', [
            'book' => $book,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/reserve', name: 'book_reserve')]
    #[IsGranted('ROLE_USER')]
    public function reserve(Book $book, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si le livre est disponible
        if (!$book->isAvailable()) {
            $this->addFlash('error', 'Ce livre n\'est pas disponible pour réservation.');
            return $this->redirectToRoute('book_show', ['id' => $book->getId()]);
        }
        
        // Créer une nouvelle réservation
        $reservation = new \App\Entity\Reservation();
        $reservation->setBook($book);
        $reservation->setUser($this->getUser());
        $reservation->activate(); // Définit le statut et la date d'échéance
        
        // Mettre à jour le nombre d'exemplaires disponibles
        $book->setAvailableCopies($book->getAvailableCopies() - 1);
        
        $entityManager->persist($reservation);
        $entityManager->flush();
        
        $this->addFlash('success', 'Livre réservé avec succès. Vous pouvez le retirer à la bibliothèque.');
        
        return $this->redirectToRoute('user_reservations');
    }

    #[Route('/category/{id}', name: 'book_by_category')]
public function byCategory(Category $category, BookRepository $bookRepository): Response
{
    $books = $bookRepository->findByCategory($category);
    
    return $this->render('book/by_category.html.twig', [
        'category' => $category,
        'books' => $books,
    ]);
}

#[Route('/author/{id}', name: 'book_by_author')]
public function byAuthor(Author $author, BookRepository $bookRepository): Response
{
    $books = $bookRepository->findBy(['author' => $author]);
    
    return $this->render('book/by_author.html.twig', [
        'author' => $author,
        'books' => $books,
    ]);
}
}