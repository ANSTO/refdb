<?php

namespace App\DataFixtures;

use App\Entity\Conference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\KernelInterface;

class ConferenceFixtures extends Fixture
{
    private $documentRoot;

    public function __construct($rootDir)
    {
        $this->documentRoot = $rootDir;
        if ($rootDir == "") {
            $this->documentRoot = getcwd();
        }
    }

    public function load($manager)
    {
        ini_set('memory_limit','2G');
        ini_set('max_execution_time', 600);

        $path = $this->documentRoot. "/src/DataFixtures/Import/";
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
