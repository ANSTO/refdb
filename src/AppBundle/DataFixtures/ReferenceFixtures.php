<?php

namespace AppBundle\DataFixtures;

use AppBundle\Entity\Conference;
use AppBundle\Service\ImportService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ReferenceFixtures extends Fixture implements DependentFixtureInterface
{
    private $kernel;
    private $importService;

    public function __construct(KernelInterface $kernel, ImportService $importService)
    {
        $this->kernel = $kernel;
        $this->importService = $importService;
    }

    public function load(ObjectManager $manager)
    {
        $path = $this->kernel->getRootDir() . "/../src/AppBundle/DataFixtures/";
        $conferences = $path . "Import/conferences.csv";

        if (($handle = fopen($conferences, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 4000, ",")) !== FALSE) {
                $filename = $path . "/References/" . $data[4] . ".csv";
                if (file_exists($filename)) {
                    $conference = $manager->getRepository(Conference::class)->findOneBy(["code"=>$data[1]]);

                    if ($conference) {
                        try {
                            $file = file($filename);
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