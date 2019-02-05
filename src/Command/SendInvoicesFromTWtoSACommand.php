<?php
/**
 * @author Artem Brovko <artem.brovko@modera.net>
 * @copyright 2019 Modera Foundation
 */

namespace App\Command;


use App\Service\SyncService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendInvoicesFromTWtoSACommand extends Command
{
    /**
     * @var SyncService
     */
    private $syncService;

    public function __construct(SyncService $syncService)
    {
        $this->syncService = $syncService;

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
        $output->writeln(print_r($this->syncService->sync(), true));
    }
}