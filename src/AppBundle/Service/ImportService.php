<?php
/**
 * Created by PhpStorm.
 * User: jdp
 * Date: 2019-02-19
 * Time: 18:33
 */

namespace AppBundle\Service;


use AppBundle\Entity\Author;
use AppBundle\Entity\Conference;
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

    private function findReferences($filename) {
        $contents = $this->csvService->open($filename);

        /** @var Reference[] $references */
        $references = array_map(function ($data) {
            $reference = new Reference();
            $reference->setAuthor(trim($data[1]));
            $reference->setOriginalAuthors(trim($data[1]));
            $reference->setTitle(rtrim(trim($data[2]),"*"));
            $reference->setPaperId(trim(strtoupper($data[0])));
            $reference->setInProc(true);
            if (isset($data[3])) {
                $reference->setPosition(trim(str_replace(" ", "-", $data[3])));
            }
            if (isset($data[4])) {
                $contributionId = trim($data[4]);
                if (filter_var($contributionId, FILTER_VALIDATE_INT)) {
                    $reference->setContributionId($contributionId);
                }
            }
            return $reference;
        }, $contents);

        // filter out header rows
        $references = array_filter($references, function(Reference $reference) {
            return (strtolower($reference->getOriginalAuthors()) != "authors");
        });

        return $references;
    }

    public function merge($filename, Conference $conference) {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 600);

        $dbReferences = $conference->getReferences();
        $fileReferences = $this->findReferences($filename);

        foreach ($fileReferences as $fileReference) {
            $fileReference->setConference($conference);
        }

        // find missing references to be remove missing references
        /** @var Reference $dbReference */
        foreach ($dbReferences as $dbReference) {
            $found = false;
            foreach ($fileReferences as $fileReference) {
                if ($dbReference->getContributionId() == $fileReference->getContributionId()) {
                    $found = true;
                }
            }
            if (!$found) {
                $this->manager->remove($dbReference);
            }
        }

        $recalculateAuthors = [];

        // find only new references
        foreach ($fileReferences as $fileReference) {
            $found = false;
            foreach ($dbReferences as $dbReference) {
                if ($dbReference->getContributionId() == $fileReference->getContributionId()) {
                    $found = true;
                    $dbReference->setPaperId($fileReference->getPaperId());
                    $dbReference->setPosition($fileReference->getPosition());
                    $dbReference->setTitle($fileReference->getTitle());
                    if ($dbReference->getOriginalAuthors() !== $fileReference->getOriginalAuthors()) {
                        $dbReference->setOriginalAuthors($fileReference->getOriginalAuthors());
                        $recalculateAuthors[] = $fileReference;
                    }
                }
            }
            if (!$found) {
                $recalculateAuthors[] = $fileReference;
                $this->manager->persist($fileReference);
            }
        }

        $this->calculateAuthors($recalculateAuthors);

        $this->manager->flush();

        return count($recalculateAuthors);

    }


    private function calculateAuthors($references) {
        $results = $this->findAuthors($references);

        $references = $results['references'];
        $newAuthors = $results['authors'];

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
    }

    /**
     * @param $filename
     * @param $conference
     * @return int
     * @throws Exception
     */
    public function import($filename, Conference $conference) {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 600);

        // delete all current references
        foreach ($conference->getReferences() as $reference) {
            $this->manager->remove($reference);
        }

        $references = $this->findReferences($filename);

        foreach ($references as $reference) {
            $reference->setConference($conference);
            $this->manager->persist($reference);
        }

        $this->calculateAuthors($references);

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