<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Reference;
use AppBundle\Entity\Search;
use AppBundle\Form\SearchType;
use AppBundle\Service\FavouriteService;
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
    public function indexAction(Request $request, FavouriteService $favouriteService) {
        $search = new Search();
        $form = $this->createForm(SearchType::class, $search);
        $form->handleRequest($request);

        $totalResults = 0;
        $references = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $query = $this->getDoctrine()->getManager()
                ->getRepository(Reference::class)->search($search);

            if ($query !== false) {
                $references = $query->setMaxResults(5)
                    ->getQuery()
                    ->getResult();

                $query = $this->getDoctrine()->getManager()
                    ->getRepository(Reference::class)->search($search);
                $totalResults = $query->select("COUNT(r)")->getQuery()->getSingleScalarResult();
            } else {

                $response["results"] = [];
            }
        }

        return $this->render('search/index.html.twig', [
            "form" => $form->createView(),
            "favourites" => $favouriteService->getFavourites(),
            "total" => $totalResults,
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
    public function searchAction(Request $request, FavouriteService $favouriteService) {

        $response = ["favourites" => $favouriteService->getFavourites()];
        $search = new Search();
        $form = $this->createForm(SearchType::class, $search);
        $form->handleRequest($request);

        $totalResults = 0;

        if ($form->isSubmitted() && $form->isValid()) {
            $query = $this->getDoctrine()->getManager()
                ->getRepository(Reference::class)->search($search);

            if ($query !== false) {
                $response["total"] = $query->select("COUNT(r)")->getQuery()->getSingleScalarResult();
                $query = $this->getDoctrine()->getManager()
                    ->getRepository(Reference::class)->search($search);
                $response["results"] = $query->setMaxResults(5)
                    ->getQuery()
                    ->getResult();
            } else {
                $response["results"] = [];
            }
        }


        return new JsonResponse($response);
    }
}
