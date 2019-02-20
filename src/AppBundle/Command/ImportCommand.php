<?php
/**
 * Created by PhpStorm.
 * User: jdp
 * Date: 2019-02-19
 * Time: 18:12
 */

namespace AppBundle\Command;


use AppBundle\Entity\Author;
use AppBundle\Entity\Conference;
use AppBundle\Entity\Reference;
use AppBundle\Service\AuthorService;
use AppBundle\Service\CsvService;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends Command
{
    private $manager;
    private $csvService;
    private $authorService;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:import-conference';

    public function __construct(ObjectManager $manager, CsvService $csvService, AuthorService $authorService)
    {
        $this->manager = $manager;
        $this->csvService = $csvService;
        $this->authorService = $authorService;

        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->addArgument("conf")
            ->addArgument("filename");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        ini_set('memory_limit','2G');
        ini_set('max_execution_time', 600);


        $conf = $input->getArgument('conf');
        $filename = $input->getArgument('filename');


        $conference = $this->manager->getRepository(Conference::class)->findOneBy(["code"=>$conf]);

        if ($conference === null) {
            $output->writeln("Could not find conference with Code: " . $conf);
            exit();
        } else {
            $output->writeln("Importing data into " . $conference->getName());
        }

        $contents = $this->csvService->open($filename);

        /** @var Reference[] $references */
        $references = array_map(function($data) {
            $reference = new Reference();
            $reference->setAuthor(trim($data[1]));
            $reference->setTitle(trim($data[2]));
            $reference->setPaperId(trim(strtoupper($data[0])));
            $reference->setInProc(true);
            $reference->setPosition(trim(str_replace(" ", "-",$data[3])));
            return $reference;
        },$contents);

        $results = $this->findAuthors($references);

        $references = $results['references'];
        $newAuthors = $results['authors'];

        ksort($newAuthors);

        foreach ($references as $reference) {
            $reference->setConference($conference);
            $this->manager->persist($reference);
        }

        //$this->manager->flush();

        $output->writeln("Adding " . count($references) . " references");

        // Add references to existing items


        foreach ($newAuthors as $name=>$newAuthorRefs) {
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
                $links = 0;
                foreach ($newAuthorRefs as $reference) {
                    if (!$dbAuthor->getReferences()->contains($reference)) {
                        $dbAuthor->addReference($reference);
                        $links++;
                    }
                }
                $output->writeln("Appending " . $links . " references to " . $name . ", an existing author");
                //$this->manager->flush();
            } elseif (count($dbAuthors) > 1) {
                echo "Failed " . $name . " has more than one entry";
                exit();
            } else {
                $newAuthor = new Author();
                $newAuthor->setName($name);
                foreach ($newAuthorRefs as $reference) {
                    if (!$newAuthor->getReferences()->contains($reference)) {
                        $newAuthor->addReference($reference);
                    }
                }

                $output->writeln("Adding author: " . $name);
                $this->manager->persist($newAuthor);

            }
        }

        $this->manager->flush();
        $output->writeln("Import completed");

        $output->writeln("Performing clean up");

        $results = $this->manager->getRepository(Author::class)
            ->createQueryBuilder("a")
            ->select("a.name, count(a.name)")
            ->having("count(a.name) > 1")
            ->groupBy("a.name")
            ->getQuery()
            ->getArrayResult();

        $removed = 0;
        foreach ($results as $result) {
            $auths = $this->manager->getRepository(Author::class)->findBy(["name"=>$result["name"]]);

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
                    $this->manager->remove($other_auth);
                    $removed++;
                }
            }

        }

        $this->manager->flush();

        $output->writeln($removed . " excess authors removed");

        //$output->writeln($contents);
    }



    private function findAuthors($references) {
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