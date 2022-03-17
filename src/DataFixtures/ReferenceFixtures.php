<?php

namespace App\DataFixtures;

use App\Entity\Conference;
use App\Service\ImportService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ReferenceFixtures extends Fixture implements DependentFixtureInterface
{
    private $documentRoot;
    private $importService;

    public function __construct($rootDir, ImportService $importService)
    {
        $this->documentRoot = $rootDir;
        if ($rootDir == "") {
            $this->documentRoot = getcwd();
        }
        $this->importService = $importService;
    }

    public function load($manager)
    {
        $path = $this->documentRoot . "/src/DataFixtures/";
        $conferences = $path . "Import/conferences.csv";

        if (($handle = fopen($conferences, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 4000, ",")) !== FALSE) {
                $filename = $path . "References/" . $data[4] . ".csv";
                if (file_exists($filename)) {
                    $conference = $manager->getRepository(Conference::class)->findOneBy(["code"=>$data[1]]);

                    if ($conference) {
                        try {
                            $file = file($filename);
                            dump($filename, file_exists($filename));
                            echo "Importing " . count($file) . " reference for " . $conference . "\n";
                            $references = $this->importService->import($filename, $conference);
                            echo "Successfully imported: " . $references . "\n";
                        } catch (\Exception $exception) {
                            echo "Failed to import: " . $data[4] . ": " . $exception->getMessage() . " \n";
                        }
                    } else {
                        echo "Failed to import: " . $data[4] . ": DB Conference not found \n";
                    }

                    $manager->clear();
                }
            }
        }
    }

    public function getDependencies()
    {
        return array(
            ConferenceFixtures::class,
        );
    }
}
