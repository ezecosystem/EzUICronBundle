<?php

namespace Smile\EzUICronBundle\Controller;

use EzSystems\PlatformUIBundle\Controller\Controller;
use Smile\CronBundle\Entity\SmileCron;
use Smile\EzUICronBundle\Service\EzCronService;

/**
 * Class StatusController
 *
 * @package Smile\EzUICronBundle\Controller
 */
class StatusController extends Controller
{
    /** @var EzCronService $cronService cron service */
    protected $cronService;

    /**
     * StatusController constructor.
     *
     * @param EzCronService $cronService cron service
     */
    public function __construct(EzCronService $cronService)
    {
        $this->cronService = $cronService;
    }

    public function listAction()
    {
        $crons = $this->cronService->listCronsStatus();
        $cronRows = array();

        foreach ($crons as $cron) {
            $cronRows[] = array(
                'alias' => $cron->getAlias(),
                'queued' => $cron instanceof SmileCron ? $cron->getQueued()->format('d-m-Y H:i') : false,
                'started' => $cron instanceof SmileCron ? $cron->getStarted()->format('d-m-Y H:i') : false,
                'ended' => $cron instanceof SmileCron ? $cron->getEnded()->format('d-m-Y H:i') : false,
                'status' => $cron instanceof SmileCron ? $cron->getStatus() : false
            );
        }

        return $this->render('SmileEzUICronBundle:cron:tab/status/list.html.twig', [
            'datas' => $cronRows
        ]);
    }
}