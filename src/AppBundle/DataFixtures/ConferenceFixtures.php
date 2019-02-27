<?php

namespace AppBundle\DataFixtures;

use AppBundle\Entity\Conference;
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
                echo "Persisting: " . $conference->getName() . "\n";
                $manager->persist($conference);
            }
        }
        $manager->flush();
    }
}