<?php
/**
 * Created by PhpStorm.
 * User: jdp
 * Date: 2019-02-19
 * Time: 18:12
 */

namespace AppBundle\Command;

use AppBundle\Entity\Conference;
use AppBundle\Service\ImportService;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Automatically re-imports any conferences which are still unpublished and has an external URL
 *
 * Class RefreshCommand
 * @package AppBundle\Command
 */
class RefreshCommand extends Command
{
    private $manager;
    private $importService;
    protected static $defaultName = 'app:refresh';

    public function __construct(ObjectManager $manager, ImportService $importService)
    {
        $this->manager = $manager;
        $this->importService = $importService;
        parent::__construct();
    }


    protected function configure()
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conferences = $this->manager
            ->getRepository(Conference::class)
            ->createQueryBuilder("c")
            ->andWhere("c.isPublished = false")
            ->getQuery()->getResult();

        /** @var Conference $conference */
        foreach ($conferences as $conference) {
            if ($conference->getImportUrl() !== null) {
                $output->writeln("Re-importing " . $conference);
                $written = $this->importService->merge($conference->getImportUrl(), $conference);
                $output->writeln($written . " references created");
            }
        }
    }
}