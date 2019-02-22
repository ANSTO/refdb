<?php

namespace AppBundle\DataFixtures;

use AppBundle\Entity\Author;
use AppBundle\Entity\Conference;
use AppBundle\Entity\Reference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\KernelInterface;

class ConferenceFixtures extends Fixture
{
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function load(ObjectManager $manager)
    {
        ini_set('memory_limit','2G');
        ini_set('max_execution_time', 600);

        $path = $this->kernel->getRootDir() . "/../src/AppBundle/DataFixtures/Import/";
        $filename = $path . "conferences.csv";


        if (($handle = fopen($filename, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 4000, ",")) !== FALSE) {
                $conference = new Conference();
                $conference->setName($data[0]);
                $conference->setCode($data[1]);
                $conference->setIsPublished(true);
                $conference->setLocation($data[2]);
                $conference->setYear($data[3]);
                echo "Persisting: " . $conference->getName() . "\r\n";
                $manager->persist($conference);
                $manager->flush();

                /*$conferenceFile = $path . "/" . $data[4] . ".csv";
                if (file_exists($conferenceFile)) {
                    $references = $this->getRows($conferenceFile);
                    echo "Importing " . count($references) . " references\r\n";
                    foreach ($references as $reference) {
                        $reference->setConference($conference);
                        $manager->persist($reference);
                    }

                    $manager->flush();
                    $manager->clear();

                    $this->cleanDuplicates($manager);
                }*/


            }
        }
    }

    private function cleanDuplicates(ObjectManager $manager) {
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

        if ($removed > 0) {
            echo "Removed " . $removed . " duplicate authors";
        }

        $manager->flush();
        $manager->clear();
    }

    private function getRows($filename) {
        $references = [];
        if (($handle = fopen($filename, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 8000, ",")) !== FALSE) {
                $reference = new Reference();
                $reference->setAuthor(trim($data[1]));
                $reference->setTitle(trim($data[2]));
                $reference->setPaperId(trim(strtoupper($data[0])));
                $reference->setInProc(true);
                $reference->setPosition(trim(str_replace(" ", "-",$data[3])));
                $references[] = $reference;
            }
        }
        return $references;
    }

}