<?php

namespace AppBundle\DataFixtures;

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
        $path = $this->kernel->getRootDir() . "/../src/AppBundle/DataFixtures/Import/";
        $filename = $path . "conferences.csv";


        if (($handle = fopen($filename, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 4000, ",")) !== FALSE) {
                $conference = new Conference();
                $conference->setName($data[0]);
                $conference->setCode($data[1]);
                $conference->setLocation($data[2]);
                $conference->setYear($data[3]);
                echo "Persisting: " . $conference->getName() . "\r\n";
                $manager->persist($conference);


//                $conferenceFile = $path . "/" . $data[4] . ".csv";
//                if (file_exists($conferenceFile)) {
//                    $references = $this->getRows($conferenceFile);
//                    /** @var Reference $reference */
//                    echo "Found " . count($references) . " references\r\n";
//                    foreach ($references as $reference) {
//                        $reference->setConference($conference);
//                        $manager->persist($reference);
//                    }
//                }
//
//                $manager->flush();
//                $manager->clear();
            }
            $manager->flush();
        }
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