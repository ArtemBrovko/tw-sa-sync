<?php
/**
 * @author Artem Brovko <artem.brovko@modera.net>
 * @copyright 2019 Modera Foundation
 */

namespace App\Controller;

use App\Service\SyncService;
use ArtemBro\SmartAccountsApiBundle\Service\SmartAccountsApiService;
use ArtemBro\TransferWiseApiBundle\Service\TransferWiseApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @var TransferWiseApiService
     */
    private $transferWiseService;

    /**
     * @var SmartAccountsApiService
     */
    private $smartAccountsApiService;

    /**
     * @var SyncService
     */
    private $syncService;

    /**
     * DefaultController constructor.
     *
     * @param TransferWiseApiService $transferWiseService
     * @param SmartAccountsApiService $smartAccountsApiService
     * @param SyncService $syncService
     */
    public function __construct(TransferWiseApiService $transferWiseService,
                                SmartAccountsApiService $smartAccountsApiService,
                                SyncService $syncService)
    {
        $this->transferWiseService = $transferWiseService;
        $this->smartAccountsApiService = $smartAccountsApiService;
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
        $syncResult = $this->syncService->sync();
        return $this->render('sync.html.twig', array(
            'imported' => $syncResult->getImported(),
            'skipped' => $syncResult->getSkipped(),
            'errors' => $syncResult->getErrors()
        ));
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
            case TransferWiseApiService::TRANSFER_STATUS_INCOMING_PAYMENT_WAITING:
                $transferWiseService->transferProcess($transferId);

            case TransferWiseApiService::TRANSFER_STATUS_PROCESSING:
                $transferWiseService->transferConvertFunds($transferId);

            case TransferWiseApiService::TRANSFER_STATUS_FUNDS_CONVERTED:
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
                case TransferWiseApiService::TRANSFER_STATUS_INCOMING_PAYMENT_WAITING:
                    $transferWiseService->transferProcess($transferId);

                case TransferWiseApiService::TRANSFER_STATUS_PROCESSING:
                    $transferWiseService->transferConvertFunds($transferId);

                case TransferWiseApiService::TRANSFER_STATUS_FUNDS_CONVERTED:
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