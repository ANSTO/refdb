<?php

namespace AppBundle\Controller;

use AppBundle\Form\SearchType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
        $form = $this->createForm(SearchType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

        }

        // search by author
        // search by conference
        // search by title
        return $this->render('search/index.html.twig', [
            "form" => $form->createView()
        ]);
    }
}
