<?php
/**
 * @author Artem Brovko <artem.brovko@modera.net>
 * @copyright 2019 Modera Foundation
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

class GetAvailableCurrenciesCommand extends Command
{
    /**
     * @var TransferWiseApiService
     */
    private $transferWiseService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(TransferWiseApiService $transferWiseService, EntityManagerInterface $entityManager)
    {
        $this->transferWiseService = $transferWiseService;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('transfer-wise:get-available-currencies')
            ->setDescription('Get available currencies from borderless account')
            ->addArgument('syncRecord', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $syncRecord = $this->entityManager->getRepository(SyncRecord::class)->find($input->getArgument('syncRecord'));

        $client = $this->transferWiseService->getClientForRecord($syncRecord);

        $table = new Table($output);

        $this->printTable($table, $client->getAvailableCurrencies());
    }

    private function printTable(Table $table, $json)
    {
        if (count($json)) {
            $table->setHeaders(array_keys(get_object_vars($json[0])));
            foreach ($json as $row) {
//                $row->details = implode(';', array_values(get_object_vars($row->details)));
                $table->addRow(array_values(get_object_vars($row)));
            }
            $table->render();
        }
    }
}