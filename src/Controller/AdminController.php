<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Reservation;
use App\Entity\Review;
use App\Entity\User;
use App\Form\BookType;
use App\Form\UserRoleType;
use App\Repository\BookRepository;
use App\Repository\ReservationRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(
        BookRepository $bookRepository,
        UserRepository $userRepository,
        ReservationRepository $reservationRepository,
        ReviewRepository $reviewRepository
    ): Response
    {
        // Statistiques pour le tableau de bord
        $stats = [
            'totalBooks' => count($bookRepository->findAll()),
            'totalUsers' => count($userRepository->findAll()),
            'activeReservations' => count($reservationRepository->findBy(['status' => Reservation::STATUS_ACTIVE])),
            'pendingReviews' => count($reviewRepository->findBy(['status' => Review::STATUS_PENDING]))
        ];
        
        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats
        ]);
    }

    #[Route('/books', name: 'admin_books')]
    public function books(BookRepository $bookRepository): Response
    {
        return $this->render('admin/books.html.twig', [
            'books' => $bookRepository->findAll()
        ]);
    }

    #[Route('/books/new', name: 'admin_book_new')]
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
            
            return $this->redirectToRoute('admin_books');
        }
        
        return $this->render('admin/book_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Ajouter un livre'
        ]);
    }

    #[Route('/books/{id}/edit', name: 'admin_book_edit')]
    public function editBook(Book $book, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'Livre modifié avec succès.');
            
            return $this->redirectToRoute('admin_books');
        }
        
        return $this->render('admin/book_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier le livre'
        ]);
    }

    #[Route('/books/{id}/delete', name: 'admin_book_delete')]
    public function deleteBook(Book $book, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($book);
        $entityManager->flush();
        
        $this->addFlash('success', 'Livre supprimé avec succès.');
        
        return $this->redirectToRoute('admin_books');
    }

    #[Route('/users', name: 'admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        return $this->render('admin/users.html.twig', [
            'users' => $userRepository->findAll()
        ]);
    }

    #[Route('/users/{id}/edit-role', name: 'admin_user_edit_role')]
    public function editUserRole(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserRoleType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'Rôle utilisateur modifié avec succès.');
            
            return $this->redirectToRoute('admin_users');
        }
        
        return $this->render('admin/user_role_form.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/reviews', name: 'admin_reviews')]
    public function reviews(ReviewRepository $reviewRepository): Response
    {
        return $this->render('admin/reviews.html.twig', [
            'reviews' => $reviewRepository->findBy(['status' => Review::STATUS_PENDING])
        ]);
    }

    #[Route('/review/{id}/approve', name: 'admin_review_approve')]
    public function approveReview(Review $review, EntityManagerInterface $entityManager): Response
    {
        $review->approve();
        $entityManager->flush();
        
        $this->addFlash('success', 'Critique approuvée.');
        
        return $this->redirectToRoute('admin_reviews');
    }

    #[Route('/review/{id}/reject', name: 'admin_review_reject')]
    public function rejectReview(Review $review, EntityManagerInterface $entityManager): Response
    {
        $review->reject();
        $entityManager->flush();
        
        $this->addFlash('success', 'Critique rejetée.');
        
        return $this->redirectToRoute('admin_reviews');
    }

    #[Route('/reservations', name: 'admin_reservations')]
    public function reservations(ReservationRepository $reservationRepository): Response
    {
        return $this->render('admin/reservations.html.twig', [
            'reservations' => $reservationRepository->findAll()
        ]);
    }
}