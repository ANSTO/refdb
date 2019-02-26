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
     * Page with no javascript functions as expected
     * @Route("/", name="homepage")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request) {
        $search = new Search();
        $form = $this->createForm(SearchType::class, $search);
        $form->handleRequest($request);

        $references = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $references = $this->getDoctrine()->getManager()
                ->getRepository(Reference::class)->search($search)->setMaxResults(5)
                ->getQuery()
                ->getResult();
        }

        return $this->render('search/index.html.twig', [
            "form" => $form->createView(),
            "references" => $references
        ]);
    }

    /**
     * Search page
     * JSON results only of page
     * @Route("/search", name="search", options={"expose"=true})
     * @param Request $request
     * @return JsonResponse
     */
    public function searchAction(Request $request) {

        $response = [];
        $search = new Search();
        $form = $this->createForm(SearchType::class, $search);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $response = $this->getDoctrine()->getManager()
                ->getRepository(Reference::class)->search($search)->setMaxResults(5)
                ->getQuery()
                ->getResult();
        }


        return new JsonResponse($response);
    }
}
