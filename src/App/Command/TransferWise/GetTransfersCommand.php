<?php
/**
 * @author Artem Brovko <brovko.artem@gmail.com>
 * @copyright 2019 Artem Brovko
 */


namespace App\Command\TransferWise;


use App\Entity\SyncRecord;
use App\Utils\PrintTableTrait;
use App\Utils\TransferWiseClientTrait;
use ArtemBro\TransferWiseApiBundle\Service\TransferWiseApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetTransfersCommand extends Command
{
    use PrintTableTrait, TransferWiseClientTrait;

    /**
     * @var TransferWiseApiService
     */
    private $transferWiseService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    protected function configure()
    {
        $this
            ->setName('transfer-wise:get-transfers')
            ->setDescription('Get transfers')
            ->addOption('profile-id', 'p', InputArgument::OPTIONAL)
            ->addArgument('syncRecord', InputArgument::REQUIRED)
            ->addArgument('id', InputArgument::OPTIONAL, 'Transfer ID');
    }

    public function __construct(TransferWiseApiService $transferWiseService, EntityManagerInterface $entityManager)
    {
        $this->transferWiseService = $transferWiseService;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $syncRecord = $this->entityManager->getRepository(SyncRecord::class)->find($input->getArgument('syncRecord'));

        $client = $this->getTAClientForRecord($this->transferWiseService, $syncRecord);

        $table = new Table($output);

        if ($input->hasArgument('id') && !empty($input->getArgument('id'))) {
            $this->printTable($table, $client->getTransfer($input->getArgument('id')));
        } else {
            $this->printTable($table, $client->getTransfers($input->getOption('profile-id')));
        }
    }
}