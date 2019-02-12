<?php
/**
 * @author Artem Brovko <artem.brovko@modera.net>
 * @copyright 2019 Modera Foundation
 */

namespace App\Command;


use App\Entity\SyncRecord;
use App\Service\SyncService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendInvoicesFromTWtoSACommand extends Command
{
    /**
     * @var SyncService
     */
    private $syncService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(SyncService $syncService, EntityManagerInterface $entityManager)
    {
        $this->syncService = $syncService;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:send-invoices-from-tw-to-sa')
            ->setDescription('Sends TransferWise invoices to SmartAccounts');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $syncRecords = $this->entityManager->getRepository(SyncRecord::class)->findAll();

        foreach ($syncRecords as $syncRecord) {
            $output->writeln(print_r($this->syncService->sync($syncRecord), true));
        }
    }
}