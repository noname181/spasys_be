<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //$schedule->command('inspire')->hourly();
        $schedule->call('App\Http\Controllers\RateData\RateDataController@check_payment')->cron('*/5 * * * *');
        $schedule->call('App\Http\Controllers\Item\ItemController@apiItemsCargoList')->cron('*/5 * * * *');
        $schedule->call('App\Http\Controllers\Item\ItemController@updateStockItemsApiNoLogin')->cron('*/6 * * * *');
        $schedule->call('App\Http\Controllers\Item\ItemController@apiItemCronNoLogin')->cron('*/7 * * * *');
        $schedule->call('App\Http\Controllers\ScheduleShipment\ScheduleShipmentController@getScheduleFromApiNoLogin')->cron('*/8 * * * *');
        $schedule->call('App\Http\Controllers\ScheduleShipment\ScheduleShipmentController@stock_history')->cron('*/9 * * * *');
        $schedule->call('App\Http\Controllers\AlarmData\AlarmDataController@insertDailyAlarm7')->cron('01 9 * * *');
        $schedule->call('App\Http\Controllers\AlarmData\AlarmDataController@insertDailyAlarm30')->cron('02 9 * * *');
        $schedule->call('App\Http\Controllers\AlarmData\AlarmDataController@insertDailyAlarmInsulace7')->cron('03 9 * * *');
        $schedule->call('App\Http\Controllers\AlarmData\AlarmDataController@insertDailyAlarmInsulace30')->cron('04 9 * * *');
        $schedule->call('App\Http\Controllers\AlarmData\AlarmDataController@alarmPw90d')->cron('05 9 * * *');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
