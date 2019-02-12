<?php

namespace App\Controller;

use App\Entity\SyncRecord;
use App\Form\SyncRecordType;
use App\Repository\SyncRecordRepository;
use App\Service\SyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/syncRecord")
 */
class SyncRecordController extends AbstractController
{
    /**
     * @var SyncService
     */
    private $syncService;

    /**
     * SyncRecordController constructor.
     *
     * @param SyncService $syncService
     */
    public function __construct(SyncService $syncService)
    {
        $this->syncService = $syncService;
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

        $syncResult = $syncService->sync($syncRecord);

        return $this->render('sync.html.twig', array(
            'imported' => $syncResult->getImported(),
            'skipped' => $syncResult->getSkipped(),
            'errors' => $syncResult->getErrors()
        ));
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
     * @return SyncService
     */
    public function getSyncService(): SyncService
    {
        return $this->syncService;
    }
}
