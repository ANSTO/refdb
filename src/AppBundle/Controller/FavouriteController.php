<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Author;
use AppBundle\Entity\Favourite;
use AppBundle\Entity\Reference;
use AppBundle\Service\DoiService;
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


        $verifyFailed = false;
        $titleIssue = false;
        $authorIssue = false;
        $pageNumberIssue = false;
        $hasWarning = false;
        foreach ($references as $reference) {
            if ($reference->getConference()->isUseDoi() && !$reference->isDoiVerified()) {
                $doiService = new DoiService();
                $valid = $doiService->check($reference);
                if (!$valid) {
                    $verifyFailed = true;
                } else {
                    $reference->setDoiVerified(true);
                    $reference->setCache($reference->__toString());
                    $this->getDoctrine()->getManager()->flush();
                }
            }

            if ($reference->hasTitleIssue()) {
                $titleIssue = true;
            }

            if (preg_match_all("/[\[\(\/]+/",$reference->getAuthor(), $matches) || count($reference->getAuthors()) == 0) {
                $authorIssue = true;
            }
            if (($reference->getConference()->isPublished() && $reference->getInProc() && ($reference->getPosition() === null || $reference->getPosition() == "" || $reference->getPosition() == "99-98"))) {
                $pageNumberIssue = true;
            }

            if (count($reference->getFeedback()) > 0) {
                $hasWarning = true;
            }
        }

        $warning = "";

        if ($hasWarning) {
            $warning .= "Please note that we have recently received feedback indicated that atleast one of these reference may have a problem.\n\n";
        }

        if ($titleIssue) {
            $warning .= "Some papers titles are all uppercase, you must correct this before using this reference.\n\n";
        }

        if ($authorIssue) {
            $warning .= "Some papers have malformed authors, please correct this before using these references.\n\n";
        }
        if ($pageNumberIssue) {
            $warning .= "The page numbers for some of these references could not be added automatically. ";
            $warning .= "You must provide the page numbers from the original proceedings which is located at JACoW.org, and substitute ‘pp. XX-XX’ with the correct page numbers.\n\n";
            //$warning .= "* Please report these numbers by clicking on the ‘Fix a problem’ button as an Admin will be able to update this reference for future results.    \n\n";
        }



        return $this->render("favourite/show.html.twig", ["references"=>$references, "warning" => $warning]);
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
