<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Author;
use AppBundle\Entity\Conference;
use AppBundle\Entity\Reference;
use AppBundle\Service\AuthorService;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
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

    /**
     * Lists all author entities.
     * @Route("/authors", name="authors_clean")
     */
    public function authorAction(Request $request, AuthorService $authorService)
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