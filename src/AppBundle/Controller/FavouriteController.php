<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Author;
use AppBundle\Entity\Favourite;
use AppBundle\Entity\Reference;
use AppBundle\Service\FavouriteService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Author controller.
 *
 * @Route("favourite")
 */
class FavouriteController extends Controller
{
    private $safeRef = "/^((?!\/\/)[a-zA-Z0-9\/._])+$/";

    /**
     * Lists all author entities.
     * @Route("/show", name="favourite_show")
     */
    public function indexAction(FavouriteService $favouriteService)
    {
        $favourites = $favouriteService->getFavourites();

        $references = [];
        $manager = $this->getDoctrine()->getManager();
        foreach ($favourites as $favourite) {
            $references[] = $manager->getRepository(Reference::class)->find($favourite);
        }

        return $this->render("favourite/show.html.twig", ["references"=>$references]);
    }

    /**
     * @Route("/remove/{id}", name="favourite_toggle_redirect")
     * @param FavouriteService $favouriteService
     * @param Reference $reference
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function toggleUpdateAction(Request $request, FavouriteService $favouriteService, Reference $reference)
    {
        $favouriteService->toggle($reference);
        if (preg_match($this->safeRef, $request->get('ref'))) {
            return $this->redirect($request->get('ref'));
        }
        return $this->redirectToRoute("favourite_show");
    }

    /**
     * @Route("/toggle/{id}", name="favourite_toggle", options={"expose"=true})
     * @param FavouriteService $favouriteService
     * @param Reference $reference
     * @return JsonResponse
     */
    public function toggleAction(FavouriteService $favouriteService, Reference $reference)
    {
        $results = $favouriteService->toggle($reference);
        return new JsonResponse($results);
    }
}
