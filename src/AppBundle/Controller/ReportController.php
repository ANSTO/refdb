<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Author;
use AppBundle\Entity\Conference;
use AppBundle\Entity\Reference;
use AppBundle\Entity\Search;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Author controller.
 *
 * @Route("report")
 */
class ReportController extends Controller
{
    /**
     * Lists all author entities.
     * @Route("/", name="report_index")
     */
    public function indexAction(Request $request)
    {
        $manager = $this->getDoctrine()->getManager();
        // missing page numbers

        $references = $manager->getRepository(Reference::class)
            ->createQueryBuilder("r")
            ->select("count(r)")
            ->getQuery()
            ->getSingleScalarResult();

        $conferences = $manager->getRepository(Conference::class)
            ->createQueryBuilder("c")
            ->select("count(c)")
            ->getQuery()
            ->getSingleScalarResult();


        $authors = $manager->getRepository(Author::class)
            ->createQueryBuilder("a")
            ->select("count(a)")
            ->getQuery()
            ->getSingleScalarResult();

        $missingPages = $manager->getRepository(Reference::class)
            ->createQueryBuilder("r")
            ->select("count(r)")
            ->where("r.position = :position")
            ->setParameter("position", "99-98")
            ->getQuery()
            ->getSingleScalarResult();

        // malformed authors
        $authorList = $manager->getRepository(Reference::class)
            ->createQueryBuilder("r")
            ->select("r.id, r.author")
            ->getQuery()
            ->getArrayResult();


        // malformed authors
        $searches = $manager->getRepository(Search::class)
            ->createQueryBuilder("s")
            ->select("count(s)")
            ->getQuery()
            ->getSingleScalarResult();

        $malformed = 0;
        $malformedIds = [];
        foreach ($authorList as $ref) {
            preg_match_all("/[\[\(\/]+/",$ref['author'], $matches);
            if (count($matches[0]) != 0) {
                $malformedIds[] = $ref['id'];
                $malformed++;
            }
        }


        $emptyConference = $manager->getRepository(Conference::class)->findEmpty();

        $empty = count($emptyConference);
        $emptyIds = [];
        /** @var Conference $conf */
        foreach ($emptyConference as $conf) {
            $emptyIds[] = $conf->getId();
        }


        return $this->render("report/index.html.twig", array(
                "references" => $references,
                "authors" => $authors,
                "conferences" => $conferences,
                "pages" => $missingPages,
                "malformed" => $malformed,
                "searches" => $searches,
                "empty" => $empty,
                "emptyIds" => $emptyIds,
                "malformedIds" => $malformedIds)
        );
    }


    /**
     * Lists all author entities.
     * @Route("/ref/id", name="id_report")
     */
    public function reportAction(Request $request)
    {
        $ids = explode(",", $request->get("filter"));
        $manager = $this->getDoctrine()->getManager();
        $query = $manager->getRepository(Reference::class)
            ->createQueryBuilder("r")
            ->select("r")
            ->where("r.id IN (:ids)")
            ->setParameter("ids", $ids);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        // parameters to template
        return $this->render('report/reference.html.twig', array('pagination' => $pagination));
    }

    /**
     * Lists all author entities.
     * @Route("/conf/id", name="id_conference")
     */
    public function conferenceAction(Request $request)
    {
        $ids = explode(",", $request->get("filter"));
        $manager = $this->getDoctrine()->getManager();
        $query = $manager->getRepository(Conference::class)
            ->createQueryBuilder("c")
            ->select("c")
            ->where("c.id IN (:ids)")
            ->setParameter("ids", $ids);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        // parameters to template
        return $this->render('report/conference.html.twig', array('pagination' => $pagination));
    }



    /**
     * Lists all author entities.
     * @Route("/pages", name="pages_report")
     */
    public function pagesAction(Request $request)
    {
        $manager = $this->getDoctrine()->getManager();
        $query = $manager->getRepository(Reference::class)
            ->createQueryBuilder("r")
            ->select("r")
            ->where("r.position = :position")
            ->setParameter("position", "99-98");


        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        // parameters to template
        return $this->render('report/reference.html.twig', array('pagination' => $pagination));
    }

}