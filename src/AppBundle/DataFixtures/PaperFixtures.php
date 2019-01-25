<?php

namespace AppBundle\DataFixtures;

use AppBundle\Entity\Conference;
use AppBundle\Entity\Reference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\KernelInterface;

class PaperFixtures extends Fixture
{
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function load(ObjectManager $manager)
    {
        $filename = $this->kernel->getRootDir() . "/../src/AppBundle/DataFixtures/Import/hb2016.txt";
        $contents = file($filename);

        $lines = count($contents);

        $papers = $lines / 4;

        $conference = $manager->getRepository(Conference::class)->findOneBy(["code"=>"HB'16"]);

        for ($p = 0; $p < $papers; $p++) {
            $l = $p * 4;
            $reference = new Reference();
            $reference->setPaperId(trim(strtoupper($contents[$l])));
            $reference->setAuthor(trim($contents[$l+1]));
            $reference->setTitle(trim($contents[$l+2]));
            $reference->setPosition(trim(str_replace(" ", "-",$contents[$l+3])));
            $reference->setConference($conference);
            $manager->persist($reference);
        }

        $manager->flush();
    }
}