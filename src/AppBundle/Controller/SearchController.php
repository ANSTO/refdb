<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Reference;
use AppBundle\Entity\Search;
use AppBundle\Form\SearchType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
class SearchController extends Controller
{
    /**
     * Search page
     *
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request) {
        $search = new Search();
        $form = $this->createForm(SearchType::class, $search);
        $form->handleRequest($request);

        $references = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($search);
            $manager->flush();
            $references = $this->getDoctrine()->getManager()
                ->getRepository(Reference::class)->search($search);
        }

        // search by author
        // search by conference
        // search by title
        return $this->render('search/index.html.twig', [
            "form" => $form->createView(),
            "references" => $references
        ]);
    }

    /**
     * Search page
     *
     * @Route("/search", name="search", options={"expose"=true})
     */
    public function searchAction(Request $request) {

        $response = [];
        $search = new Search();
        $form = $this->createForm(SearchType::class, $search);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($search);
            $manager->flush();
            $response = $this->getDoctrine()->getManager()
                ->getRepository(Reference::class)->search($search);
        }


        return new JsonResponse($response);
    }
}
