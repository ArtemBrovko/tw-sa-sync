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

class GetTransfersCommand extends Command
{
    /**
     * @var TransferWiseApiService
     */
    private $transferWiseService;

    protected function configure()
    {
        $this
            ->setName('transfer-wise:get-transfers')
            ->setDescription('Get transfers')
            ->addArgument('id', InputArgument::OPTIONAL, 'Transfer ID');
    }

    public function __construct(TransferWiseApiService $transferWiseService)
    {
        $this->transferWiseService = $transferWiseService;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if ($input->hasArgument('id')) {
            print_r($this->transferWiseService->getTransfer($input->getArgument('id')));
        } else {
            print_r($this->transferWiseService->getTransfers());
        }
    }
}