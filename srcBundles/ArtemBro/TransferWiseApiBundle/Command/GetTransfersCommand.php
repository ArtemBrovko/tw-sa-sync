<?php
/**
 * @author Artem Brovko <brovko.artem@gmail.com>
 * @copyright 2019 Artem Brovko
 */


namespace ArtemBro\TransferWiseApiBundle\Command;


use App\Entity\SyncRecord;
use ArtemBro\TransferWiseApiBundle\Service\TransferWiseApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetTransfersCommand extends Command
{
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

        $client = $this->transferWiseService->getClientForRecord($syncRecord);

        $table = new Table($output);

        if ($input->hasArgument('id')) {
            $this->printTable($table, $client->getTransfer($input->getArgument('id')));
        } else {
            $this->printTable($table, $client->getTransfers());
        }
    }

    private function printTable(Table $table, $json)
    {
        if (count($json)) {
            $table->setHeaders(array_keys(get_object_vars($json[0])));
            foreach ($json as $row) {
                $row->details = implode(';', array_values(get_object_vars($row->details)));
                $table->addRow(array_values(get_object_vars($row)));
            }
            $table->render();
        }
    }
}