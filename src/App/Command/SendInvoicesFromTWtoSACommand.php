<?php
/**
 * @author Artem Brovko <brovko.artem@gmail.com>
 * @copyright 2019 Artem Brovko
 */

namespace App\Command;


use App\Entity\SyncRecord;
use App\Service\SyncService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
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
        $syncRecords = $this->entityManager->getRepository(SyncRecord::class)->findBy(['active' => true]);

        $table = new Table($output->section());
        $table->setHeaders(['ID', 'Name', 'Added', 'Skipped']);
        $table->render();

        foreach ($syncRecords as $syncRecord) {
            $syncResult = $this->syncService->sync($syncRecord);
            $table->appendRow([$syncRecord->getId(), $syncRecord->getName(), count($syncResult->getImported()), count($syncResult->getSkipped())]);
        }
    }
}