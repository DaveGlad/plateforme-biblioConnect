<?php

namespace App\Controller;

use App\Form\UserProfileType;
use App\Repository\ReservationRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    #[Route('/profile', name: 'user_profile')]
    public function profile(): Response
    {
        return $this->render('user/profile.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/profile/edit', name: 'user_profile_edit')]
    public function editProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'Profil mis à jour avec succès.');
            
            return $this->redirectToRoute('user_profile');
        }
        
        return $this->render('user/profile_edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reservations', name: 'user_reservations')]
    public function reservations(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        $reservations = $reservationRepository->findBy(['user' => $user], ['reservationDate' => 'DESC']);
        
        return $this->render('user/reservations.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/reviews', name: 'user_reviews')]
    public function reviews(ReviewRepository $reviewRepository): Response
    {
        $user = $this->getUser();
        $reviews = $reviewRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        
        return $this->render('user/reviews.html.twig', [
            'reviews' => $reviews,
        ]);
    }

    #[Route('/reservation/{id}/cancel', name: 'user_reservation_cancel')]
    public function cancelReservation(
        \App\Entity\Reservation $reservation, 
        EntityManagerInterface $entityManager
    ): Response
    {
        // Vérifier que la réservation appartient bien à l'utilisateur courant
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à annuler cette réservation.');
        }
        
        // Vérifier que la réservation est en attente ou active (pas déjà retournée/annulée)
        if ($reservation->getStatus() !== \App\Entity\Reservation::STATUS_PENDING && 
            $reservation->getStatus() !== \App\Entity\Reservation::STATUS_ACTIVE) {
            $this->addFlash('error', 'Cette réservation ne peut pas être annulée.');
            return $this->redirectToRoute('user_reservations');
        }
        
        // Annuler la réservation
        $reservation->cancel();
        
        // Si le livre était déjà emprunté, remettre un exemplaire en disponibilité
        if ($reservation->getStatus() === \App\Entity\Reservation::STATUS_ACTIVE) {
            $book = $reservation->getBook();
            $book->setAvailableCopies($book->getAvailableCopies() + 1);
        }
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Réservation annulée avec succès.');
        
        return $this->redirectToRoute('user_reservations');
    }
}