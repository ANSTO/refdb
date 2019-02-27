<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Conference;
use AppBundle\Service\CurrentConferenceService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Conference controller.
 *
 * @Route("conference")
 */
class ConferenceController extends Controller
{
    private $safeRef = "/^((?!\/\/)[a-zA-Z0-9\/._])+$/";

    /**
     * Dismiss the notification for current conference.
     *
     * @Route("/dismiss", name="conference_dismiss")
     * @param Request $request
     * @param CurrentConferenceService $currentConferenceService
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function dismissAction(Request $request, CurrentConferenceService $currentConferenceService) {
        // Confirm referral URL is internal only (only contains alpha num and slash)
        if (preg_match($this->safeRef, $request->get('ref'))) {
            $currentConferenceService->dismiss();
            return $this->redirect($request->get('ref'));
        }
        return $this->redirectToRoute("homepage");
    }

    /**
     * Clear the 'current conference' option
     *
     * @Route("/clear/current", name="conference_current_clear")
     */
    public function clearAction(Request $request, CurrentConferenceService $currentConferenceService) {
        if (preg_match($this->safeRef, $request->get('ref'))) {
            $currentConferenceService->clearCurrent();
            return $this->redirect($request->get('ref'));
        }
        return $this->redirectToRoute("homepage");
    }

    /**
     * Make this conference my current conference (changes the way the reference appears)
     *
     * @Route("/current/{id}", name="conference_current")
     */
    public function currentAction(Request $request, CurrentConferenceService $currentConferenceService, Conference $conference) {
        if (preg_match($this->safeRef, $request->get('ref'))) {
            $currentConferenceService->setCurrent($conference);
            return $this->redirect($request->get('ref'));
        }
        return $this->redirectToRoute("homepage");
    }

    /**
     * Lists all conference entities.
     * @Route("/", name="conference_index")
     */
    public function indexAction(Request $request)
    {
        $manager = $this->getDoctrine()->getManager();
        $query = $manager->getRepository(Conference::class)
            ->createQueryBuilder("c")
         #   ->innerJoin("c.references", "r")
            ->getQuery();

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        // parameters to template
        return $this->render('conference/index.html.twig', array('pagination' => $pagination));
    }

    /**
     * Creates a new conference entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/new", name="conference_new")
     */
    public function newAction(Request $request)
    {
        $conference = new Conference();
        $form = $this->createForm('AppBundle\Form\ConferenceType', $conference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($conference);
            $em->flush();

            return $this->redirectToRoute('conference_show', array('id' => $conference->getId()));
        }

        return $this->render('conference/new.html.twig', array(
            'conference' => $conference,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a conference entity.
     *
     * @Route("/show/{id}", name="conference_show")
     */
    public function showAction(Conference $conference)
    {
        $deleteForm = $this->createDeleteForm($conference);

        return $this->render('conference/show.html.twig', array(
            'conference' => $conference,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing conference entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/edit/{id}", name="conference_edit")
     */
    public function editAction(Request $request, Conference $conference)
    {
        $deleteForm = $this->createDeleteForm($conference);
        $editForm = $this->createForm('AppBundle\Form\ConferenceType', $conference);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('conference_edit', array('id' => $conference->getId()));
        }

        return $this->render('conference/edit.html.twig', array(
            'conference' => $conference,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a conference entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/delete/{id}", name="conference_delete")
     */
    public function deleteAction(Request $request, Conference $conference)
    {
        $form = $this->createDeleteForm($conference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($conference);
            $em->flush();
            return new JsonResponse([
                "success" => true,
                "redirect" => $this->generateUrl("conference_index")]);
        }

        return $this->render("conference/delete.html.twig", array("delete_form"=>$form->createView()));
    }

    /**
     * Creates a form to delete a conference entity.
     *
     * @param Conference $conference The conference entity
     *
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    private function createDeleteForm(Conference $conference)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('conference_delete', array('id' => $conference->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

    /**
     * @Route("/search/{query}/{type}", name="conference_search", options={"expose"=true})
     * @param $query
     * @param string $type
     * @return JsonResponse
     */
    public function searchAction($query, $type = "name") {
        $results = $this->getDoctrine()->getManager()->getRepository(Conference::class)
            ->search($query, $type);
        return new JsonResponse($results);
    }
}
