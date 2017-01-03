<?php

namespace Smile\EzUICronBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Smile\CronBundle\Cron\CronHandler;
use Smile\CronBundle\Cron\CronInterface;
use Smile\CronBundle\Entity\SmileCron;
use Smile\CronBundle\Service\CronService;
use Smile\EzUICronBundle\Entity\SmileEzCron;
use Smile\EzUICronBundle\Repository\SmileEzCronRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EzCronService
{
    /** @var CronService $cronService cron service */
    protected $cronService;

    /** @var CronHandler $cronHandler */
    protected $cronHandler;

    /** @var SmileEzCronRepository $repository */
    protected $repository;

    public function __construct(CronService $cronService, CronHandler $cronHandler, Registry $doctrineRegistry)
    {
        $this->cronService = $cronService;
        $this->cronHandler = $cronHandler;
        $entityManager = $doctrineRegistry->getManager();
        $this->repository = $entityManager->getRepository('SmileEzUICronBundle:SmileEzCron');
    }

    /**
     * Return cron status entries
     *
     * @return SmileCron[] cron status entries
     */
    public function listCronsStatus()
    {
        return $this->cronService->listCronsStatus();
    }

    /**
     * Return cron list detail
     *
     * @return array cron list
     */
    public function getCrons()
    {
        /** @var CronInterface[] $crons */
        $crons = $this->cronService->getCrons();

        /** @var SmileEzCron[] $ezCrons */
        $ezCrons = $this->repository->listCrons();

        $return = array();

        foreach ($ezCrons as $ezCron) {
            $return[$ezCron->getAlias()] = array(
                'alias' => $ezCron->getAlias(),
                'expression' => $ezCron->getExpression(),
                'arguments' => $ezCron->getArguments(),
                'priority' => (int)$ezCron->getPriority(),
                'enabled' => (int)$ezCron->getEnabled() == 1
            );
        }

        foreach ($crons as $cron) {
            if (!isset($return[$cron->getAlias()])) {
                $return[$cron->getAlias()] = array(
                    'alias' => $cron->getAlias(),
                    'expression' => $cron->getExpression(),
                    'arguments' => $cron->getArguments(),
                    'priority' => (int)$cron->getPriority(),
                    'enabled' => true
                );
            }
        }

        return $return;
    }

    public function isQueued($alias)
    {
        return $this->cronService->isQueued($alias);
    }

    public function addQueued($alias)
    {
        $this->cronService->addQueued($alias);
    }

    public function runQueued(InputInterface $input, OutputInterface $output)
    {
        /** @var SmileCron[] $smileCrons */
        $smileCrons = $this->cronService->listQueued();

        /** @var CronInterface[] $crons */
        $crons = $this->cronHandler->getCrons();

        /** @var array() $eZCrons */
        $eZCrons = $this->getCrons();

        $cronAlias = array();

        foreach ($crons as $cron) {
            if (isset($eZCrons[$cron->getAlias()])) {
                $cronAlias[$cron->getAlias()] = array(
                    'cron' => $cron,
                    'arguments' => $eZCrons[$cron->getAlias()]['arguments']
                );
            }
        }

        if ($smileCrons) {
            foreach ($smileCrons as $smileCron) {
                if (isset($cronAlias[$smileCron->getAlias()])) {
                    $this->cronService->run($smileCron);
                    /** @var CronInterface $cron */
                    $cron = $cronAlias[$smileCron->getAlias()]['cron'];
                    $cron->addArguments($cronAlias[$smileCron->getAlias()]['arguments']);
                    $status = $cron->run($input, $output);
                    $this->cronService->end($smileCron, $status);
                }
            }
        }
    }
}