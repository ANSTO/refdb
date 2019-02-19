<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Conference;
use AppBundle\Entity\Reference;
use AppBundle\Form\SwitchConferenceType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Reference controller.
 *
 * @Route("fix")
 */
class FixController extends Controller
{
    /**
     * Lists all reference entities.
     * @Route("/landing/{id}", name="fix_landing")
     */
    public function landingAction(Request $request, Reference $reference)
    {
        if ($reference->getOverride() === null)
        {
            return $this->render('fix/landing.html.twig', array('reference' => $reference));
        }
    }

    /**
     * Lists all reference entities.
     * @Route("/conference/{id}", name="fix_conference")
     */
    public function conferenceAction(Request $request, Reference $reference) {
        return $this->render('fix/conference.html.twig', array('reference' => $reference));
    }

    /**
     * Lists all reference entities.
     * @Route("/conference/{id}/switch", name="fix_conference_switch")
     */
    public function conferenceSwitchAction(Request $request, Reference $originalReference) {

        $reference = new Reference();
        $reference->setPublicSubmission(true);
        $reference->setReplaces($originalReference);

        $form = $this->createForm(SwitchConferenceType::class, $reference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($reference);
            $em->flush();
        }

        return $this->render('fix/conference_switch.html.twig', array('reference' => $reference, 'form' => $form->createView()));
    }
    /**
     * Lists all reference entities.
     * @Route("/conference/{id}/add", name="fix_conference_add")
     */
    public function conferenceaAddAction(Request $request, Reference $reference) {

        $conference = new Conference();
        $conference->setPublicSubmission(true);
        $conference->setReplaces($reference->getConference());
        $form = $this->createForm('AppBundle\Form\ConferenceType', $conference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($conference);
            $em->flush();
        }

        return $this->render('fix/conference_add.html.twig', array('reference' => $reference, 'form' => $form->createView()));
    }
    /**
     * Lists all reference entities.
     * @Route("/conference/{id}/alter", name="fix_conference_alter")
     */
    public function conferenceAlterAction(Request $request, Reference $reference) {
        return $this->render('fix/conference_alter.html.twig', array('reference' => $reference));
    }

    public function titleAction(Request $request, Reference $reference) {

    }

    public function paperAction(Request $request, Reference $reference) {

    }

}
