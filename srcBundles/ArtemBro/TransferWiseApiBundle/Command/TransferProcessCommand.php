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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TransferProcessCommand extends Command
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
            ->setName('transfer-wise:simulation:process')
            ->setDescription('Simulate transfer processing')
            ->addArgument('syncRecord', InputArgument::REQUIRED)
            ->addArgument('id', InputArgument::OPTIONAL, 'Transfer ID');
    }

    public function __construct(TransferWiseApiService $transferWiseService, EntityManagerInterface $entityManager)
    {
        $this->transferWiseService = $transferWiseService;
        $this->entityManager = $entityManager;

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
        $syncRecord = $this->entityManager->getRepository(SyncRecord::class)->find($input->getArgument('syncRecord'));

        $client = $this->transferWiseService->getClientForRecord($syncRecord);

        print_r($client->transferProcess($input->getArgument('id')));
    }
}