<?php
/**
 * @author Artem Brovko <artem.brovko@modera.net>
 * @copyright 2019 Modera Foundation
 */

namespace App\Command\SmartAccounts;

use App\Entity\SyncRecord;
use App\Utils\PrintTableTrait;
use App\Utils\SmartAccountClientTrait;
use ArtemBro\SmartAccountsApiBundle\Service\SmartAccountsApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetClientsCommand extends Command
{
    use PrintTableTrait, SmartAccountClientTrait;

    /**
     * @var SmartAccountsApiService
     */
    private $smartAccountsApiService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    protected function configure()
    {
        $this
            ->setName('smart-accounts:get-clients')
            ->setDescription('Get Smart Account clients')
            ->addArgument('syncRecord', InputArgument::REQUIRED);
    }

    public function __construct(SmartAccountsApiService $smartAccountsApiService, EntityManagerInterface $entityManager)
    {
        $this->smartAccountsApiService = $smartAccountsApiService;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $syncRecord = $this->entityManager->getRepository(SyncRecord::class)->find($input->getArgument('syncRecord'));

        $client = $this->getSAClientForRecord($this->smartAccountsApiService, $syncRecord);

        $table = new Table($output);

        $this->printTable($table, $client->getProfiles());
    }
}