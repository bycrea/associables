<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\Category;
use AppBundle\Form\CategoryType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Simplification de la Route pour les méthodes du controller
 * en ajoutant le commentaire ci-dessous pour éviter de nommer les routes
 * en commencant par '/categories'/... à chaque fois
 * @Route("/categories")
 */
class CategoriesController extends Controller
{
    /**
     * @Route("/", name="admin_categories")
     */
    public function indexAction(Request $request)
    {
        $categories = $this->getDoctrine()->getRepository(Category::class)->findAll();

        return $this->render('admin/categories/index.html.twig', [
            'title' => 'Categories',
            'categories' => $categories
        ]);
    }

    /**
     * @Route("/create", name="admin_categories_create")
     */
    public function createAction(Request $request)
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', 'La categorie a bien été ajouté.');
            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('admin/categories/form.html.twig', [
            'title' => 'Creation categorie',
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/update/{id}", name="admin_categories_update")
     */
    public function updateAction(Request $request, $id)
    {
        $category = $this->getDoctrine()->getRepository(Category::class)->find($id);
        $form = $this->createForm(CategoryType::class, $category);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', 'La categorie a bien été modifié.');
            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('admin/categories/form.html.twig', [
            'title' => 'Edition categorie',
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/delete/{id}", name="admin_categories_delete")
     */
    public function deleteAction($id)
    {
        $category = $this->getDoctrine()->getRepository(Category::class)->find($id);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($category);
        $entityManager->flush();

        $this->addFlash('success', 'La categorie a bien été supprimé.');
        return $this->redirectToRoute('admin_categories');
    }
}