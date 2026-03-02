<?php

namespace App\Scheduler;

use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('site_checks')]
class SiteChecksSchedule implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        $schedule = new Schedule();

        // Add your scheduled tasks here, for example:
        // $schedule->add(
        //     RecurringMessage::every('5 minutes', new YourMessage())
        // );

        return $schedule;
    }
}
