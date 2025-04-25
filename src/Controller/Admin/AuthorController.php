<?php

namespace App\Controller\Admin;

use App\Entity\Author;
use App\Form\AuthorType;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/authors')]
#[IsGranted('ROLE_ADMIN')]
class AuthorController extends AbstractController
{
    #[Route('/', name: 'admin_authors')]
    public function index(AuthorRepository $authorRepository): Response
    {
        $authors = $authorRepository->findAll();
        
        return $this->render('admin/authors/index.html.twig', [
            'authors' => $authors,
        ]);
    }
    
    #[Route('/new', name: 'admin_author_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $author = new Author();
        $form = $this->createForm(AuthorType::class, $author);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($author);
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'auteur a été créé avec succès.');
            
            return $this->redirectToRoute('admin_authors');
        }
        
        return $this->render('admin/authors/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Ajouter un auteur',
        ]);
    }
    
    #[Route('/{id}/edit', name: 'admin_author_edit')]
    public function edit(Author $author, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AuthorType::class, $author);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'auteur a été modifié avec succès.');
            
            return $this->redirectToRoute('admin_authors');
        }
        
        return $this->render('admin/authors/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier l\'auteur',
            'author' => $author,
        ]);
    }
    
    #[Route('/{id}/delete', name: 'admin_author_delete')]
    public function delete(Author $author, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'auteur est associé à des livres
        if ($author->getBooks()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer cet auteur car il est associé à ' . $author->getBooks()->count() . ' livre(s).');
            return $this->redirectToRoute('admin_authors');
        }
        
        $entityManager->remove($author);
        $entityManager->flush();
        
        $this->addFlash('success', 'L\'auteur a été supprimé avec succès.');
        
        return $this->redirectToRoute('admin_authors');
    }
}