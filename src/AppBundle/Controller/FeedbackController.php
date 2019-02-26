<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Feedback;
use AppBundle\Entity\Reference;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Feedback controller.
 */
class FeedbackController extends Controller
{
    /**
     * Lists all feedback entities.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/admin/feedback", name="feedback_index")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $manager = $this->getDoctrine()->getManager();
        $query = $manager->getRepository(Feedback::class)
            ->createQueryBuilder("f")
            ->innerJoin("f.reference", "r")
            ->getQuery();

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        // parameters to template
        return $this->render('feedback/index.html.twig', array('pagination' => $pagination));
    }

    /**
     * Creates a new feedback entity.
     * @Route("/feedback/{id}", name="feedback_new")
     * @param Request $request
     * @param Reference $reference
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request, Reference $reference)
    {
        $feedback = new Feedback();
        /** @var Reference $reference */
        $feedback->setReference($reference);
        $form = $this->createForm('AppBundle\Form\FeedbackType', $feedback, ["action"=>$this->generateUrl("feedback_new", [
            "id" => $reference->getId()])]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($feedback);
            $em->flush();

            // TODO: Send notification email to admins

            $this->addFlash("notice", "Your feedback has been sent to our administrators. Thank you.");
            return new JsonResponse([
                "success" => true,
                "redirect" => $this->generateUrl("reference_show",array('id' => $reference->getId()))]);
        }

        return $this->render('feedback/new.html.twig', array(
            'feedback' => $feedback,
            'form' => $form->createView(),
        ));
    }


    /**
     * Deletes a feedback entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/admin/feeback/delete/{id}", name="feedback_delete")
     * @param Request $request
     * @param Feedback $feedback
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, Feedback $feedback)
    {
        $form = $this->createDeleteForm($feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($feedback);
            $em->flush();
            return new JsonResponse([
                "success" => true,
                "redirect" => $this->generateUrl("feedback_index")]);
        }

        return $this->render("feedback/delete.html.twig", array("delete_form"=>$form->createView()));
    }

    /**
     * Creates a form to delete a feedback entity.
     *
     * @param Feedback $feedback The feedback entity
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createDeleteForm(Feedback $feedback)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('feedback_delete', array('id' => $feedback->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
