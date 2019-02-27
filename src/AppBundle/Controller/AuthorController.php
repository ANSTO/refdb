<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Author;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Author controller.
 *
 * @Route("author")
 */
class AuthorController extends Controller
{
    /**
     * Lists all author entities.
     * @Route("/", name="author_index")
     */
    public function indexAction(Request $request)
    {
        $manager = $this->getDoctrine()->getManager();
        $query = $manager->getRepository(Author::class)
            ->createQueryBuilder("a")
            ->getQuery();

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        // parameters to template
        return $this->render('author/index.html.twig', array('pagination' => $pagination));
    }

    /**
     * Creates a new author entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/new", name="author_new")
     */
    public function newAction(Request $request)
    {
        $author = new Author();
        $form = $this->createForm('AppBundle\Form\AuthorType', $author);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($author);
            $em->flush();

            return $this->redirectToRoute('author_show', array('id' => $author->getId()));
        }

        return $this->render('author/new.html.twig', array(
            'author' => $author,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a author entity.
     *
     * @Route("/show/{id}", name="author_show")
     */
    public function showAction(Author $author)
    {
        $deleteForm = $this->createDeleteForm($author);

        return $this->render('author/show.html.twig', array(
            'author' => $author,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing author entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/edit/{id}", name="author_edit")
     */
    public function editAction(Request $request, Author $author)
    {
        $deleteForm = $this->createDeleteForm($author);
        $editForm = $this->createForm('AppBundle\Form\AuthorType', $author);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('author_edit', array('id' => $author->getId()));
        }

        return $this->render('author/edit.html.twig', array(
            'author' => $author,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a author entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/delete/{id}", name="author_delete")
     */
    public function deleteAction(Request $request, Author $author)
    {
        $form = $this->createDeleteForm($author);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($author);
            $em->flush();
            return new JsonResponse([
                "success" => true,
                "redirect" => $this->generateUrl("author_index")]);
        }

        return $this->render("author/delete.html.twig", array("delete_form"=>$form->createView()));
    }

    /**
     * Creates a form to delete a author entity.
     *
     * @param Author $author The author entity
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createDeleteForm(Author $author)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('author_delete', array('id' => $author->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * @Route("/search/{query}", name="author_search", options={"expose"=true})
     */
    public function searchAction($query) {
        $results = $this->getDoctrine()->getManager()->getRepository(Author::class)
            ->search($query);
        return new JsonResponse($results);
    }
}
