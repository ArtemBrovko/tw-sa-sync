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

class GetProfilesCommand extends Command
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
            ->setName('transfer-wise:get-profiles')
            ->setDescription('Get user profiles')
            ->addArgument('syncRecord', InputArgument::REQUIRED)
            ->addArgument('id', InputArgument::OPTIONAL, 'Profile ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $syncRecord = $this->entityManager->getRepository(SyncRecord::class)->find($input->getArgument('syncRecord'));

        $client = $this->transferWiseService->getClientForRecord($syncRecord);

        $table = new Table($output);

        if ($input->hasArgument('id') && !empty($input->getArgument('id'))) {
            print_r($client->getProfile($input->getArgument('id')));
        } else {
            $this->printTable($table, $client->getProfiles());
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