<?php
/**
 * Created by PhpStorm.
 * User: jdp
 * Date: 2019-02-19
 * Time: 18:33
 */

namespace AppBundle\Service;


use AppBundle\Entity\Author;
use AppBundle\Entity\Reference;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;

class ImportService
{
    private $manager;
    private $csvService;
    private $authorService;
    private $doiService;

    public function __construct(ObjectManager $manager, CsvService $csvService, AuthorService $authorService, DoiService $doiService)
    {
        $this->manager = $manager;
        $this->csvService = $csvService;
        $this->authorService = $authorService;
        $this->doiService = $doiService;
    }

    /**
     * @param $filename
     * @param $conference
     * @throws Exception
     */
    public function import($filename, $conference) {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 600);

        // delete all current references
        foreach ($conference->getReferences() as $reference) {
            $this->manager->remove($reference);
        }

        $contents = $this->csvService->open($filename);

        /** @var Reference[] $references */
        $references = array_map(function ($data) {
            $reference = new Reference();
            $reference->setAuthor(trim($data[1]));
            $reference->setOriginalAuthors(trim($data[1]));
            $reference->setTitle(trim($data[2]));
            $reference->setPaperId(trim(strtoupper($data[0])));
            $reference->setInProc(true);
            if (isset($data[3])) {
                $reference->setPosition(trim(str_replace(" ", "-", $data[3])));
            }
            return $reference;
        }, $contents);

        // filter out header rows
        $references = array_filter($references, function(Reference $reference) {
            return (strtolower($reference->getOriginalAuthors()) != "author");
        });

        $results = $this->findAuthors($references);

        $references = $results['references'];
        $newAuthors = $results['authors'];

        // Persist all the references
        foreach ($references as $reference) {
            $reference->setConference($conference);
            $this->manager->persist($reference);
        }

        // Add all the links to the authors
        foreach ($newAuthors as $name => $newAuthorRefs) {
            /** @var Author $dbAuthor */
            $dbAuthors = $this->manager->getRepository(Author::class)
                ->createQueryBuilder("a")
                ->leftJoin("a.references", "r")
                ->where("a.name = :name")
                ->setParameter("name", $name)
                ->getQuery()
                ->getResult();

            if (count($dbAuthors) == 1) {
                $dbAuthor = $dbAuthors[0];
                foreach ($newAuthorRefs as $reference) {
                    if (!$dbAuthor->getReferences()->contains($reference)) {
                        $dbAuthor->addReference($reference);
                    }
                }
            } elseif (count($dbAuthors) > 1) {
                throw new Exception("Multiple authors found: " . $name);
            } else {
                $newAuthor = new Author();
                $newAuthor->setName($name);
                foreach ($newAuthorRefs as $reference) {
                    if (!$newAuthor->getReferences()->contains($reference)) {
                        $newAuthor->addReference($reference);
                    }
                }
                $this->manager->persist($newAuthor);
            }
        }

        $this->manager->flush();

        return count($references);
    }

    private function findAuthors($references)
    {
        $authors = [];

        /** @var Reference $reference */
        foreach ($references as $reference) {
            $results = $this->authorService->parse($reference->getAuthor());

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