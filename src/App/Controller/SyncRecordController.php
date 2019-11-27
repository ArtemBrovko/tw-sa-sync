<?php

namespace App\Controller;

use App\Entity\SyncRecord;
use App\Form\SyncRecordType;
use App\Repository\SyncRecordRepository;
use App\Service\SyncService;
use App\Utils\TransferWiseClientTrait;
use ArtemBro\TransferWiseApiBundle\Service\TransferWiseApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/syncRecord")
 */
class SyncRecordController extends AbstractController
{
    use TransferWiseClientTrait;

    /**
     * @var SyncService
     */
    private $syncService;

    /**
     * @var TransferWiseApiService
     */
    private $transferWiseApiService;

    /**
     * SyncRecordController constructor.
     *
     * @param SyncService $syncService
     */
    public function __construct(SyncService $syncService, TransferWiseApiService $transferWiseApiService)
    {
        $this->syncService = $syncService;
        $this->transferWiseApiService = $transferWiseApiService;
    }

    /**
     * @Route("/", name="sync_record_index", methods={"GET"})
     */
    public function index(SyncRecordRepository $syncRecordRepository): Response
    {
        return $this->render('sync_record/index.html.twig', [
            'sync_records' => $syncRecordRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="sync_record_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $syncRecord = new SyncRecord();
        $form = $this->createForm(SyncRecordType::class, $syncRecord);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($syncRecord);
            $entityManager->flush();

            return $this->redirectToRoute('sync_record_index');
        }

        return $this->render('sync_record/new.html.twig', [
            'sync_record' => $syncRecord,
            'form'        => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="sync_record_show", methods={"GET"}, requirements={"id"="\d"})
     */
    public function show(SyncRecord $syncRecord): Response
    {
        return $this->render('sync_record/show.html.twig', [
            'sync_record' => $syncRecord,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="sync_record_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, SyncRecord $syncRecord): Response
    {
        $form = $this->createForm(SyncRecordType::class, $syncRecord);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('sync_record_index', [
                'id' => $syncRecord->getId(),
            ]);
        }

        return $this->render('sync_record/edit.html.twig', [
            'sync_record' => $syncRecord,
            'form'        => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="sync_record_delete", methods={"DELETE"})
     */
    public function delete(Request $request, SyncRecord $syncRecord): Response
    {
        if ($this->isCsrfTokenValid('delete' . $syncRecord->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($syncRecord);
            $entityManager->flush();
        }

        return $this->redirectToRoute('sync_record_index');
    }

    /**
     * @Route("/{id}/run", name="sync_record_run")
     * @param Request $request
     * @param SyncRecord $syncRecord
     *
     * @return Response
     */
    public function run(Request $request, SyncRecord $syncRecord): Response
    {
        $syncService = $this->getSyncService();
        $syncService->setDryRun(false);

        try {
            $syncResult = $syncService->sync($syncRecord);

            return $this->render('sync.html.twig', array(
                'syncRecord'  => $syncRecord,
                'isDryRun'    => $syncService->isDryRun(),
                'imported'    => $syncResult->getImported(),
                'skipped'     => $syncResult->getSkipped(),
                'wontProcess' => $syncResult->getWontProcess(),
                'errors'      => $syncResult->getErrors(),
            ));
        } catch (\Exception $e) {
            return $this->render('error.html.twig', array(
                'syncRecord' => $syncRecord,
                'message'    => $e->getMessage(),
            ));
        }
    }

    /**
     * @return SyncService
     */
    public function getSyncService(): SyncService
    {
        return $this->syncService;
    }

    /**
     * @Route("/{id}/dryRun", name="sync_record_dry_run")
     * @param Request $request
     * @param SyncRecord $syncRecord
     *
     * @return Response
     */
    public function dryRun(Request $request, SyncRecord $syncRecord): Response
    {
        $syncService = $this->getSyncService();

        $syncService->setDryRun(true);

        try {
            $syncResult = $syncService->sync($syncRecord);

            return $this->render('sync.html.twig', array(
                'syncRecord'  => $syncRecord,
                'isDryRun'    => $syncService->isDryRun(),
                'imported'    => $syncResult->getImported(),
                'skipped'     => $syncResult->getSkipped(),
                'wontProcess' => $syncResult->getWontProcess(),
                'errors'      => $syncResult->getErrors(),
            ));
        } catch (\Exception $e) {
            return $this->render('error.html.twig', array(
                'syncRecord' => $syncRecord,
                'message'    => $e->getMessage(),
            ));
        }
    }

    /**
     * @Route("/run-all", name="sync_run_all", methods={"GET"})
     */
    public function runAll(Request $request): Response
    {
        $syncService = $this->getSyncService();

        $syncRecords = $this->getDoctrine()->getManager()->getRepository(SyncRecord::class)->findAll();
        $syncResults = [];

        foreach ($syncRecords as $syncRecord) {
            $syncResults[] = $syncService->sync($syncRecord);
        }

        return new Response(print_r($syncResults, true));
    }

    /**
     * @Route("/{id}/simulate}", name="sync_simulate_transfer", methods={"POST"}, requirements={"id"="\d+"})
     * @param Request $request
     * @param SyncRecord $syncRecord
     *
     * @return Response
     */
    public function simulate(Request $request, SyncRecord $syncRecord)
    {
        $transferWiseClient = $this->getTAClientForRecord($this->transferWiseApiService, $syncRecord);

        $transferId = $request->request->get('transferId');
        $transfer = $transferWiseClient->getTransfer($transferId);

        $skipped = 0;
        $updated = 0;
        switch ($transfer->status) {
            case TransferWiseApiService::TRANSFER_STATUS_INCOMING_PAYMENT_WAITING:
                $transferWiseClient->transferProcess($transferId);

            case TransferWiseApiService::TRANSFER_STATUS_PROCESSING:
                $transferWiseClient->transferConvertFunds($transferId);

            case TransferWiseApiService::TRANSFER_STATUS_FUNDS_CONVERTED:
                $transfer = $transferWiseClient->transferSendOutgoingPayment($transferId);
                ++$updated;
                break;

            default:
                ++$skipped;
        }

        return $this->render('sync_record/transfers.html.twig', array(
            'sync_record'  => $syncRecord,
            'transfers'    => [$transfer],
            'skippedCount' => $skipped,
            'updatedCount' => $updated,
        ));
    }
}
