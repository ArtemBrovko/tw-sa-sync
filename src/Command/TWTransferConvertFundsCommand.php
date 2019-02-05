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

class TWTransferConvertFundsCommand extends Command
{
    /**
     * @var TransferWiseService
     */
    private $transferWiseService;

    protected function configure()
    {
        $this
            ->setName('tw:simulation:convert-funds')
            ->setDescription('Simulate funds convertion for transfer')
            ->addArgument('id', InputArgument::OPTIONAL, 'Transfer ID');
    }

    /**
     * TWTransferConvertFundsCommand constructor.
     *
     * @param TransferWiseService $transferWiseService
     */
    public function __construct(TransferWiseService $transferWiseService)
    {
        $this->transferWiseService = $transferWiseService;

        parent::__construct();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        print_r($this->transferWiseService->transferConvertFunds($input->getArgument('id')));
    }
}