<?php
/**
 * Created by PhpStorm.
 * User: jdp
 * Date: 2019-02-19
 * Time: 18:12
 */

namespace App\Command;

use App\Entity\Conference;
use App\Service\AdminNotifyService;
use App\Service\ImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Automatically re-imports any conferences which are still unpublished and has an external URL
 *
 * Class RefreshCommand
 * @package App\Command
 */
class RefreshCommand extends Command
{
    private $manager;
    private $importService;
    private $adminNotificationService;
    protected static $defaultName = 'app:refresh';

    public function __construct(EntityManagerInterface $manager, ImportService $importService, AdminNotifyService $adminNotificationService)
    {
        $this->manager = $manager;
        $this->importService = $importService;
        $this->adminNotificationService = $adminNotificationService;
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
                try {
                    $written = $this->importService->merge($conference->getImportUrl(), $conference);
                    $output->writeln($written . " references created");
                } catch (\Exception $exception) {
                    $message = " Failed updating " . $conference . "\n\n " . $exception->getMessage();
                    $this->adminNotificationService->sendAll("Automatic Import Failed", $message);
                    $output->writeln($message);
                }

            }
        }
    }
}
