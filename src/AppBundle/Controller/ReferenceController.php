<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Author;
use AppBundle\Entity\Conference;
use AppBundle\Entity\Reference;
use AppBundle\Form\Type\TagsAsInputType;
use AppBundle\Service\DoiService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
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

        return $this->render('reference/index.html.twig', array('pagination' => $pagination));
    }

    /**
     * Creates a new reference entity.
     * @IsGranted("ROLE_ADMIN")
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
     * @Route("/show/{id}", name="reference_show", options={"expose"=true})
     * @param Reference $reference
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(Reference $reference)
    {
        $warning = false;

        if ($reference->getConference()->isUseDoi() && !$reference->isDoiVerified()) {
            $doiService = new DoiService();
            $valid = $doiService->check($reference);
            if (!$valid) {
                $warning = "This references DOI could not be verified, so it has been removed.";
            } else {
                $reference->setDoiVerified(true);
                $reference->setCache($reference->__toString());
                $this->getDoctrine()->getManager()->flush();
            }
        }

        if (preg_match_all("/[\[\(\/]+/",$reference->getAuthor(), $matches) || count($reference->getAuthors()) == 0) {
            $warning = "There is a problem with this papers authors";
        }
        if ($reference->getPosition() == "99-98") {
            $warning = "The page number (position in proceedings) is not included in this reference. 99-98 is a placeholder for missing data.";
        }


        $deleteForm = $this->createDeleteForm($reference);

        return $this->render('reference/show.html.twig', array(
            'reference' => $reference,
            'warning' => $warning,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing reference entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/edit/{id}", name="reference_edit")
     */
    public function editAction(Request $request, Reference $reference)
    {
        $manager = $this->getDoctrine()->getManager();
        $deleteForm = $this->createDeleteForm($reference);
        $originalAuthors = clone $reference->getAuthors();

        $editForm = $this->createForm('AppBundle\Form\ReferenceType', $reference)
            ->add('authors', TagsAsInputType::class, [
                "entity_class"=> Author::class,
                "data_source" => "author_search",
                "label"=> "Associated Authors (un-ordered)"]);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $newAuthors = $reference->getAuthors();

            /** @var Author $author */
            foreach ($originalAuthors as $author) {
                if ($newAuthors->contains($author) == false) {
                    $linkedAuthor = $manager->getRepository(Author::class)->find($author->getId());
                    $linkedAuthor->removeReference($reference);
                }
            }

            /** @var Author $author */
            foreach ($newAuthors as $author) {
                if ($originalAuthors->contains($author) == false) {
                    $author->addReference($reference);
                    $manager->persist($author);
                }
            }

            $manager->flush();

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
     * @IsGranted("ROLE_ADMIN")
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
            return new JsonResponse([
                "success" => true,
                "redirect" => $this->generateUrl("reference_index")]);
        }

        return $this->render("reference/delete.html.twig", array("delete_form"=>$form->createView()));
    }


    /**
     * Creates a form to delete a reference entity.
     *
     * @param Reference $reference The reference entity
     *
     * @return \Symfony\Component\Form\FormInterface
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
