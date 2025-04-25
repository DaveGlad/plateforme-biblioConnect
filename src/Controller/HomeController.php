<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(BookRepository $bookRepository): Response
    {
        // Récupérer les livres récents et populaires pour la page d'accueil
        $recentBooks = $bookRepository->findBy([], ['id' => 'DESC'], 6);
        
        return $this->render('home/index.html.twig', [
            'recent_books' => $recentBooks,
        ]);
    }

    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function profile(): Response
    {
        // Si l'utilisateur est connecté avec des rôles spécifiques, rediriger vers le tableau de bord approprié
        $user = $this->getUser();
        
        if ($user->isAdmin()) {
            return $this->redirectToRoute('admin_dashboard');
        } elseif ($user->isLibrarian()) {
            return $this->redirectToRoute('librarian_dashboard');
        }
        
        // Sinon, afficher le profil utilisateur normal
        return $this->render('user/profile.html.twig');
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('home/contact.html.twig');
    }
}