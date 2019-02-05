<?php
/**
 * @author Artem Brovko <artem.brovko@modera.net>
 * @copyright 2019 Modera Foundation
 */

namespace App\Controller;

use App\Service\SmartAccountsService;
use App\Service\SyncService;
use App\Service\TransferWiseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @var TransferWiseService
     */
    private $transferWiseService;

    /**
     * @var SmartAccountsService
     */
    private $smartAccountsService;

    /**
     * @var SyncService
     */
    private $syncService;

    /**
     * DefaultController constructor.
     *
     * @param TransferWiseService $transferWiseService
     * @param SmartAccountsService $smartAccountsService
     * @param SyncService $syncService
     */
    public function __construct(TransferWiseService $transferWiseService,
                                SmartAccountsService $smartAccountsService,
                                SyncService $syncService)
    {
        $this->transferWiseService = $transferWiseService;
        $this->smartAccountsService = $smartAccountsService;
        $this->syncService = $syncService;
    }

    public function index()
    {
        return $this->render('index.html.twig');
    }

    /**
     * @Route("/sync")
     */
    public function syncAction()
    {
        return $this->render('sync.html.twig', $this->syncService->sync());
    }

    /**
     * @Route("/simulate")
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return Response
     */
    public function simulateAction(Request $request)
    {
        $transferId = $request->get('transferId');

        $transferWiseService = $this->transferWiseService;
        $transfer = $transferWiseService->getTransfer($transferId);

        $skipped = 0;
        $updated = 0;
        switch ($transfer->status) {
            case TransferWiseService::TRANSFER_STATUS_INCOMING_PAYMENT_WAITING:
                $transferWiseService->transferProcess($transferId);

            case TransferWiseService::TRANSFER_STATUS_PROCESSING:
                $transferWiseService->transferConvertFunds($transferId);

            case TransferWiseService::TRANSFER_STATUS_FUNDS_CONVERTED:
                $transfer = $transferWiseService->transferSendOutgoingPayment($transferId);
                ++$updated;
                break;

            default:
                ++$skipped;
        }

        return $this->render('transfers.html.twig', array(
            'transfers'    => [$transfer],
            'skippedCount' => $skipped,
            'updatedCount' => $updated,
        ));

    }

    /**
     * @Route("/simulateAll")
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return Response
     */
    public function simulateAllAction()
    {
        $transferWiseService = $this->transferWiseService;


        $simulatedTransfers = [];
        $skipped = 0;
        $updated = 0;

        foreach ($transferWiseService->getTransfers() as $transfer) {
            $transferId = $transfer->id;

            switch ($transfer->status) {
                case TransferWiseService::TRANSFER_STATUS_INCOMING_PAYMENT_WAITING:
                    $transferWiseService->transferProcess($transferId);

                case TransferWiseService::TRANSFER_STATUS_PROCESSING:
                    $transferWiseService->transferConvertFunds($transferId);

                case TransferWiseService::TRANSFER_STATUS_FUNDS_CONVERTED:
                    $simulatedTransfers[] = $transferWiseService->transferSendOutgoingPayment($transferId);
                    ++$updated;
                    break;

                default:
                    $simulatedTransfers[] = $transferWiseService->transferSendOutgoingPayment($transferId);
                    ++$skipped;
            }
        }

        return $this->render('transfers.html.twig', array(
            'transfers'    => $simulatedTransfers,
            'skippedCount' => $skipped,
            'updatedCount' => $updated,
        ));
    }
}