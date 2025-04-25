<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Reservation;
use App\Form\BookType;
use App\Repository\BookRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/librarian')]
#[IsGranted('ROLE_LIBRARIAN')]
class LibrarianController extends AbstractController
{
    #[Route('/', name: 'librarian_dashboard')]
    public function dashboard(BookRepository $bookRepository, ReservationRepository $reservationRepository): Response
    {
        // Statistiques pour le tableau de bord des bibliothécaires
        $stats = [
            'totalBooks' => count($bookRepository->findAll()),
            'availableBooks' => count($bookRepository->findBy(['availableCopies' => ['>', 0]])),
            'activeReservations' => count($reservationRepository->findBy(['status' => Reservation::STATUS_ACTIVE])),
            'pendingReservations' => count($reservationRepository->findBy(['status' => Reservation::STATUS_PENDING])),
        ];
        
        // Livres avec peu d'exemplaires disponibles (alerte stock bas)
        $lowStockBooks = $bookRepository->findLowStock();
        
        return $this->render('librarian/dashboard.html.twig', [
            'stats' => $stats,
            'lowStockBooks' => $lowStockBooks
        ]);
    }

    #[Route('/books', name: 'librarian_books')]
    public function books(BookRepository $bookRepository): Response
    {
        return $this->render('librarian/books.html.twig', [
            'books' => $bookRepository->findAll()
        ]);
    }

    #[Route('/books/new', name: 'librarian_book_new')]
    public function newBook(Request $request, EntityManagerInterface $entityManager): Response
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // S'assurer que le nombre d'exemplaires disponibles correspond au total au début
            if ($book->getAvailableCopies() === null) {
                $book->setAvailableCopies($book->getTotalCopies());
            }
            
            $entityManager->persist($book);
            $entityManager->flush();
            
            $this->addFlash('success', 'Livre ajouté avec succès.');
            
            return $this->redirectToRoute('librarian_books');
        }
        
        return $this->render('librarian/book_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Ajouter un livre'
        ]);
    }

    #[Route('/books/{id}/edit', name: 'librarian_book_edit')]
    public function editBook(Book $book, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'Livre modifié avec succès.');
            
            return $this->redirectToRoute('librarian_books');
        }
        
        return $this->render('librarian/book_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier le livre'
        ]);
    }

    #[Route('/reservations', name: 'librarian_reservations')]
    public function reservations(ReservationRepository $reservationRepository): Response
    {
        $activeReservations = $reservationRepository->findBy(['status' => Reservation::STATUS_ACTIVE]);
        $pendingReservations = $reservationRepository->findBy(['status' => Reservation::STATUS_PENDING]);
        
        return $this->render('librarian/reservations.html.twig', [
            'active_reservations' => $activeReservations,
            'pending_reservations' => $pendingReservations
        ]);
    }

    #[Route('/reservation/{id}/return', name: 'librarian_reservation_return')]
    public function returnBook(Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $book = $reservation->getBook();
        
        // Marquer comme retourné
        $reservation->markAsReturned();
        
        // Augmenter le nombre d'exemplaires disponibles
        $book->setAvailableCopies($book->getAvailableCopies() + 1);
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Livre retourné avec succès.');
        
        return $this->redirectToRoute('librarian_reservations');
    }

    #[Route('/reservation/{id}/activate', name: 'librarian_reservation_activate')]
    public function activateReservation(Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que le statut est bien "en attente"
        if ($reservation->getStatus() !== Reservation::STATUS_PENDING) {
            $this->addFlash('error', 'Cette réservation ne peut pas être activée.');
            return $this->redirectToRoute('librarian_reservations');
        }
        
        $reservation->activate();
        $entityManager->flush();
        
        $this->addFlash('success', 'Réservation activée avec succès.');
        
        return $this->redirectToRoute('librarian_reservations');
    }
}