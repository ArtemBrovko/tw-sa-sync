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

class TransferSimulateCommand extends Command
{
    /**
     * @var TransferWiseApiService
     */
    private $transferWiseService;

    protected function configure()
    {
        $this
            ->setName('transfer-wise:simulation:simulate')
            ->setDescription('Simulate funds convertion for transfer')
            ->addArgument('id', InputArgument::OPTIONAL, 'Transfer ID');
    }

    /**
     * TWTransferSimulateCommand constructor.
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

        $tws = $this->transferWiseService;
        $transferId = $input->getArgument('id');

        $transfer = $tws->getTransfer($transferId);

        switch ($transfer->status) {
            case TransferWiseApiService::TRANSFER_STATUS_INCOMING_PAYMENT_WAITING:
                print_r($this->transferWiseService->transferProcess($transferId));

            case TransferWiseApiService::TRANSFER_STATUS_PROCESSING:
                print_r($this->transferWiseService->transferConvertFunds($transferId));

            case TransferWiseApiService::TRANSFER_STATUS_FUNDS_CONVERTED:
                print_r($this->transferWiseService->transferSendOutgoingPayment($transferId));
        }
    }
}