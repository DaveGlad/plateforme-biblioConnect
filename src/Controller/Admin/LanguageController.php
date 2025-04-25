<?php

namespace App\Controller\Admin;

use App\Entity\Language;
use App\Form\LanguageType;
use App\Repository\LanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/languages')]
#[IsGranted('ROLE_ADMIN')]
class LanguageController extends AbstractController
{
    #[Route('/', name: 'admin_languages')]
    public function index(LanguageRepository $languageRepository): Response
    {
        $languages = $languageRepository->findAll();
        
        return $this->render('admin/languages/index.html.twig', [
            'languages' => $languages,
        ]);
    }
    
    #[Route('/new', name: 'admin_language_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $language = new Language();
        $form = $this->createForm(LanguageType::class, $language);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($language);
            $entityManager->flush();
            
            $this->addFlash('success', 'La langue a été créée avec succès.');
            
            return $this->redirectToRoute('admin_languages');
        }
        
        return $this->render('admin/languages/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Ajouter une langue',
        ]);
    }
    
    #[Route('/{id}/edit', name: 'admin_language_edit')]
    public function edit(Language $language, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LanguageType::class, $language);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'La langue a été modifiée avec succès.');
            
            return $this->redirectToRoute('admin_languages');
        }
        
        return $this->render('admin/languages/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier la langue',
            'language' => $language,
        ]);
    }
    
    #[Route('/{id}/delete', name: 'admin_language_delete')]
    public function delete(Language $language, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si la langue est utilisée par des livres
        if ($language->getBooks()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer cette langue car elle est utilisée par ' . $language->getBooks()->count() . ' livre(s).');
            return $this->redirectToRoute('admin_languages');
        }
        
        $entityManager->remove($language);
        $entityManager->flush();
        
        $this->addFlash('success', 'La langue a été supprimée avec succès.');
        
        return $this->redirectToRoute('admin_languages');
    }
}