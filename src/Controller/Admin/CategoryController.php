<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/categories')]
#[IsGranted('ROLE_ADMIN')]
class CategoryController extends AbstractController
{
    #[Route('/', name: 'admin_categories')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();
        
        return $this->render('admin/categories/index.html.twig', [
            'categories' => $categories,
        ]);
    }
    
    #[Route('/new', name: 'admin_category_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();
            
            $this->addFlash('success', 'La catégorie a été créée avec succès.');
            
            return $this->redirectToRoute('admin_categories');
        }
        
        return $this->render('admin/categories/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Ajouter une catégorie',
        ]);
    }
    
    #[Route('/{id}/edit', name: 'admin_category_edit')]
    public function edit(Category $category, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'La catégorie a été modifiée avec succès.');
            
            return $this->redirectToRoute('admin_categories');
        }
        
        return $this->render('admin/categories/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier la catégorie',
            'category' => $category,
        ]);
    }
    
    #[Route('/{id}/delete', name: 'admin_category_delete')]
    public function delete(Category $category, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si la catégorie est utilisée par des livres
        if ($category->getBooks()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer cette catégorie car elle est utilisée par ' . $category->getBooks()->count() . ' livre(s).');
            return $this->redirectToRoute('admin_categories');
        }
        
        $entityManager->remove($category);
        $entityManager->flush();
        
        $this->addFlash('success', 'La catégorie a été supprimée avec succès.');
        
        return $this->redirectToRoute('admin_categories');
    }
}