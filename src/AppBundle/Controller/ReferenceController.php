<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Conference;
use AppBundle\Entity\Reference;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Reference controller.
 *
 * @Route("reference")
 */
class ReferenceController extends Controller
{
    /**
     * Lists all reference entities.
     * @Route("/", name="reference_index")
     */
    public function indexAction(Request $request)
    {
        $manager    = $this->getDoctrine()->getManager();
        $query = $manager->getRepository(Reference::class)
            ->createQueryBuilder("r")
            ->getQuery();

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        // parameters to template
        return $this->render('reference/index.html.twig', array('pagination' => $pagination));
    }

    /**
     * Creates a new reference entity.
     *
     * @Route("/new/{id}", name="reference_new", defaults={"id": null})
     * @param Request $request
     * @param Conference|null $conference
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request, Conference $conference = null)
    {
        $reference = new Reference();
        $reference->setConference($conference);
        $form = $this->createForm('AppBundle\Form\ReferenceType', $reference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($reference);
            $em->flush();

            return $this->redirectToRoute('reference_show', array('id' => $reference->getId()));
        }

        return $this->render('reference/new.html.twig', array(
            'reference' => $reference,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a reference entity.
     *
     * @Route("/show/{id}", name="reference_show")
     */
    public function showAction(Reference $reference)
    {
        $deleteForm = $this->createDeleteForm($reference);

        return $this->render('reference/show.html.twig', array(
            'reference' => $reference,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing reference entity.
     *
     * @Route("/edit/{id}", name="reference_edit")
     */
    public function editAction(Request $request, Reference $reference)
    {
        $deleteForm = $this->createDeleteForm($reference);
        $editForm = $this->createForm('AppBundle\Form\ReferenceType', $reference);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('reference_show', array('id' => $reference->getId()));
        }

        return $this->render('reference/edit.html.twig', array(
            'reference' => $reference,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a reference entity.
     *
     * @Route("/delete/{id}", name="reference_delete")
     */
    public function deleteAction(Request $request, Reference $reference)
    {
        $form = $this->createDeleteForm($reference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($reference);
            $em->flush();
        }

        return $this->redirectToRoute('reference_index');
    }

    /**
     * Creates a form to delete a reference entity.
     *
     * @param Reference $reference The reference entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Reference $reference)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('reference_delete', array('id' => $reference->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
