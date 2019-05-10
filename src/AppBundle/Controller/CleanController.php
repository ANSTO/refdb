<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Author;
use AppBundle\Entity\Favourite;
use AppBundle\Entity\Reference;
use AppBundle\Service\AuthorService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Clean controller, for basic data cleansing purposes
 *
 * @Route("clean")
 */
class CleanController extends Controller
{
    /**
     * Regenerate cache for all references.
     * @Route("/duplicate", name="duplicate_clean")
     */
    public function duplicateAction() {
        $count = 0;
        $manager = $this->getDoctrine()->getManager();
        /** @var Reference[] $results */
        $results = $manager->getRepository(Reference::class)
            ->createQueryBuilder("r")
            ->select("c.id as conference, r.contributionId, max(r.id) as max")
            ->join("r.conference","c")
            ->where("r.contributionId is not null")
            ->groupBy("c.id")
            ->addGroupBy("r.contributionId")
            ->having("count(r.id) > 1")
            ->getQuery()
            ->getArrayResult();

        foreach ($results as $result) {
            $max = $manager->getRepository(Reference::class)->find($result['max']);

            $references = $manager->getRepository(Reference::class)
                ->createQueryBuilder("r")
                ->select("r, favs, feeds")
                ->leftJoin("r.favourites", "favs")
                ->leftJoin("r.feedback", "feeds")
                ->join("r.conference","c")
                ->where("c.id = :conference")
                ->setParameter("conference", $result["conference"])
                ->andWhere("r.contributionId = :contributionId")
                ->setParameter("contributionId", $result["contributionId"])
                ->andWhere("r.id != :max")
                ->setParameter("max", $result['max'])
                ->getQuery()
                ->getResult();


            /** @var Reference $reference */
            foreach ($references as $reference) {
                /** @var Favourite $favourite */
                foreach ($reference->getFavourites() as $favourite) {
                    $favourite->setReference($max);
                }
                foreach ($reference->getFeedback() as $favourite) {
                    $favourite->setReference($max);
                }
                $count++;
                $manager->remove($reference);
            }
        }

        $manager->flush();

    }

    /**
     * Regenerate cache for all references.
     * @Route("/cache", name="cache_clean")
     */
    public function cacheAction()
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

        $manager->flush();

        return $this->redirectToRoute("upload_index");
    }

    /**
     * Completely reset all authors
     * @Route("/authors", name="authors_clean")
     * @param AuthorService $authorService
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function authorAction(AuthorService $authorService)
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 600);

        $manager = $this->getDoctrine()->getManager();

        /** @var Reference[] $results */
        $references = $manager->getRepository(Reference::class)->findAll();

        /** @var Reference $reference */
        foreach ($references as $reference) {
            $reference->setAuthors(new ArrayCollection());
        }
        $manager->flush();
        $manager->clear();

        $authors = $manager->getRepository(Author::class)->findAll();

        /** @var Reference $reference */
        foreach ($authors as $author) {
            $manager->remove($author);
        }
        $manager->flush();
        $manager->clear();

        $references = $manager->getRepository(Reference::class)->findAll();
        $results = $this->findAuthors($references, $authorService);
        $newAuthors = $results['authors'];

        // All authors should be new.
        foreach ($newAuthors as $name => $newAuthorRefs) {
            $newAuthor = new Author();
            $newAuthor->setName($name);
            foreach ($newAuthorRefs as $reference) {
                if (!$newAuthor->getReferences()->contains($reference)) {
                    $newAuthor->addReference($reference);
                }
            }
            $manager->persist($newAuthor);
        }

        $manager->flush();

        return $this->redirectToRoute("upload_index");
    }

    private function findAuthors($references, AuthorService $authorService)
    {
        $authors = [];

        /** @var Reference $reference */
        foreach ($references as $reference) {
            $results = $authorService->parse($reference->getOriginalAuthors());

            $reference->setAuthor($results['text']);

            foreach ($results['authors'] as $author) {
                if (!isset($authors[$author])) {
                    $authors[$author] = [];
                }
                $authors[$author][] = $reference;
            }
        }

        return ["references" => $references, "authors" => $authors];
    }
}
