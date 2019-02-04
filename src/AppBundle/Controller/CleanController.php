<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Author;
use AppBundle\Entity\Reference;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Author controller.
 *
 * @Route("clean")
 */
class CleanController extends Controller
{

    /**
     * Lists all author entities.
     * @Route("/author/list", name="reference_author_clean")
     */
    public function authorListAction(Request $request)
    {
        $manager = $this->getDoctrine()->getManager();
        $references = $manager->getRepository(Reference::class)->findBy([]);
        /** @var Reference $ref */
        foreach ($references as $ref) {
            $ref->setAuthor($ref->getAuthor() . " ");
        }
        $manager->flush();

        return $this->redirectToRoute("author_clean");
    }
    /**
     * Lists all author entities.
     * @Route("/authors", name="author_clean")
     */
    public function authorAction(Request $request)
    {
        $manager = $this->getDoctrine()->getManager();
        $results = $manager->getRepository(Author::class)
            ->createQueryBuilder("a")
            ->select("a.name, count(a.name)")
            ->having("count(a.name) > 1")
            ->groupBy("a.name")
            ->getQuery()
            ->getArrayResult();

        $removed = 0;
        foreach ($results as $result) {
            $auths = $manager->getRepository(Author::class)->findBy(["name"=>$result["name"]]);

            $auth = $auths[0];
            if (count($auths) > 1) {
                /** @var Author[] $other_auths */
                $other_auths = array_slice($auths, 1);
                foreach ($other_auths as $other_auth) {
                    foreach ($other_auth->getReferences() as $other_auth_ref) {
                        if (!$auth->getReferences()->contains($other_auth_ref)) {
                            $auth->addReference($other_auth_ref);
                        }
                    }
                    $manager->remove($other_auth);
                    $removed++;
                }
            }

        }

        //echo $removed . " excess authors removed";

        $manager->flush();


        return $this->redirectToRoute("cache_clean");
    }


    /**
     * Lists all author entities.
     * @Route("/cache", name="cache_clean")
     */
    public function cacheAction(Request $request)
    {
        $manager = $this->getDoctrine()->getManager();
        /** @var Reference[] $results */
        $results = $manager->getRepository(Reference::class)
            ->createQueryBuilder("r")
            ->select("r")
            ->getQuery()
            ->getResult();

        $cleaned = 0;
        foreach ($results as $result) {
            if ($result->getCache() !== $result->__toString()) {
                $result->setCache($result->__toString());
                $cleaned++;
            }

        }

        //echo $cleaned . " total adjustments made";
        $manager->flush();


        return $this->redirectToRoute("upload_index");
    }


}