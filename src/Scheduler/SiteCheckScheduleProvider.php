<?php

declare(strict_types=1);

namespace App\Scheduler;

use App\Message\DispatchSiteChecksMessage;
use App\Repository\SiteRepository;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule('site_checks')]
class SiteCheckScheduleProvider implements ScheduleProviderInterface
{
    public function __construct(
        private readonly SiteRepository $siteRepository,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * Gets schedule for site checks.
     *
     * @return Schedule
     */
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
                RecurringMessage::every('1 minute', new DispatchSiteChecksMessage())
            )
            ->stateful($this->cache);
    }
}
