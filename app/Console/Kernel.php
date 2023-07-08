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
        $schedule->call('App\Http\Controllers\Item\ItemController@apiItemsCargoList')->name('apiItemsCargoList')->withoutOverlapping()->cron('*/5 * * * *');
        $schedule->call('App\Http\Controllers\RateData\RateDataController@check_payment')->name('check_payment')->withoutOverlapping()->cron('*/10 * * * *');
        $schedule->call('App\Http\Controllers\Item\ItemController@updateStockItemsApiNoLogin')->name('updateStockItemsApiNoLogin')->withoutOverlapping()->cron('*/15 * * * *');
        $schedule->call('App\Http\Controllers\Item\ItemController@apiItemCronNoLogin')->name('apiItemCronNoLogin')->withoutOverlapping()->cron('*/20 * * * *');
        $schedule->call('App\Http\Controllers\ScheduleShipment\ScheduleShipmentController@getScheduleFromApiNoLogin')->name('getScheduleFromApiNoLogin')->withoutOverlapping()->cron('*/30 * * * *');
        $schedule->call('App\Http\Controllers\ScheduleShipment\ScheduleShipmentController@stock_history')->name('stock_history')->withoutOverlapping()->cron('*/35 * * * *');
        $schedule->call('App\Http\Controllers\Item\ItemController@createBondedSettlement')->name('createBondedSettlement')->withoutOverlapping()->cron('*/40 * * * *');
        
        $schedule->call('App\Http\Controllers\Item\ItemController@updateStockCompanyApiNoLogin')->name('updateStockCompanyApiNoLogin')->withoutOverlapping()->cron('01 0 * * *');
        $schedule->call('App\Http\Controllers\AlarmData\AlarmDataController@insertDailyAlarm7')->name('insertDailyAlarm7')->withoutOverlapping()->cron('01 9 * * *');
        $schedule->call('App\Http\Controllers\AlarmData\AlarmDataController@insertDailyAlarm30')->name('insertDailyAlarm30')->withoutOverlapping()->cron('02 9 * * *');
        $schedule->call('App\Http\Controllers\AlarmData\AlarmDataController@insertDailyAlarmInsulace7')->name('insertDailyAlarmInsulace7')->withoutOverlapping()->cron('03 9 * * *');
        $schedule->call('App\Http\Controllers\AlarmData\AlarmDataController@insertDailyAlarmInsulace30')->name('insertDailyAlarmInsulace30')->withoutOverlapping()->cron('04 9 * * *');
        $schedule->call('App\Http\Controllers\AlarmData\AlarmDataController@alarmPw90d')->name('alarmPw90d')->withoutOverlapping()->cron('05 9 * * *');
        $schedule->call('App\Http\Controllers\Item\ItemController@updateStockCompanyApiNoLogin')->name('updateStockCompanyApiNoLogin2')->withoutOverlapping()->cron('00 10 * * *');

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
