<?php
namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $env   = config('app.env');
        $email = config('mail.username');

        if ($env === 'production') {
            //Scheduling backup, specify the time when the backup will get cleaned & time when it will run.
            //$schedule->command('backup:run')->dailyAt('06:30');

            //Schedule to create recurring invoices
            //$schedule->command('pos:generateSubscriptionInvoices')->dailyAt('07:00');
            //$schedule->command('pos:updateRewardPoints')->dailyAt('07:30');
        }

        if ($env === 'staging') {
            //Scheduling backup, specify the time when the backup will get cleaned & time when it will run.
            // $schedule->command('backup:run')->dailyAt('05:30');
        }

        if ($env === 'demo' && ! empty($email)) {
            //IMPORTANT NOTE: This command will delete all business details and create dummy business, run only in demo server.
            //$schedule->command('pos:dummyBusiness') ->cron('0 */3 * * *')
            //->everyThirtyMinutes()->emailOutputTo($email);
        }

        //Schedule to report ZATCA invoices
        $schedule->command('app:report_zatca_invoices')->dailyAt('06:00');
        $schedule->command('app:report_failed_zatca_invoices')->dailyAt('07:00');
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
