<?php
/**
 * @author Artem Brovko <artem.brovko@modera.net>
 * @copyright 2019 Modera Foundation
 */


namespace ArtemBro\TransferWiseApiBundle\Command;


use ArtemBro\TransferWiseApiBundle\Service\TransferWiseApiService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FundCommand extends Command
{
    /**
     * @var TransferWiseApiService
     */
    private $transferWiseService;

    protected function configure()
    {
        $this
            ->setName('transfer-wise:transfer:fund')
            ->setDescription('Sends TransferWise invoices to SmartAccounts')
            ->addArgument('paymentId', InputArgument::REQUIRED, 'ID of payment');
    }

    public function __construct(TransferWiseApiService $transferWiseService)
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