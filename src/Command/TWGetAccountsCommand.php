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

class TWGetAccountsCommand extends Command
{
    /**
     * @var TransferWiseService
     */
    private $transferWiseService;

    protected function configure()
    {
        $this
            ->setName('tw:get-accounts')
            ->setDescription('Get transfers')
            ->addArgument('id', InputArgument::OPTIONAL, 'Account ID');
    }

    public function __construct(TransferWiseService $transferWiseService)
    {
        $this->transferWiseService = $transferWiseService;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if ($input->hasArgument('id')) {
            print_r($this->transferWiseService->getAccount($input->getArgument('id')));
        } else {
            print_r($this->transferWiseService->getAccounts());
        }
    }
}