<?php
/**
 * @author Artem Brovko <artem.brovko@modera.net>
 * @copyright 2019 Modera Foundation
 */


namespace App\Command;


use App\Service\TransferWiseService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TWFundCommand extends Command
{
    /**
     * @var TransferWiseService
     */
    private $transferWiseService;

    protected function configure()
    {
        $this
            ->setName('tw:transfer:fund')
            ->setDescription('Sends TransferWise invoices to SmartAccounts')
            ->addArgument('paymentId', InputArgument::REQUIRED, 'ID of payment');
    }

    public function __construct(TransferWiseService $transferWiseService)
    {
        $this->transferWiseService = $transferWiseService;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $paymentId = $input->getArgument('paymentId');

        $output->writeln(print_r($this->transferWiseService->fund($paymentId), true));
    }
}