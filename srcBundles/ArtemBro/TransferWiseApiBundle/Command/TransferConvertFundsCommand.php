<?php
/**
 * @author Artem Brovko <brovko.artem@gmail.com>
 * @copyright 2019 Artem Brovko
 */


namespace ArtemBro\TransferWiseApiBundle\Command;


use ArtemBro\TransferWiseApiBundle\Service\TransferWiseApiService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TransferConvertFundsCommand extends Command
{
    /**
     * @var TransferWiseApiService
     */
    private $transferWiseService;

    protected function configure()
    {
        $this
            ->setName('transfer-wise:simulation:convert-funds')
            ->setDescription('Simulate funds convertion for transfer')
            ->addArgument('id', InputArgument::OPTIONAL, 'Transfer ID');
    }

    /**
     * TWTransferConvertFundsCommand constructor.
     *
     * @param TransferWiseApiService $transferWiseService
     */
    public function __construct(TransferWiseApiService $transferWiseService)
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