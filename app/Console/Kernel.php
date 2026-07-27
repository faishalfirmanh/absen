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
        // $schedule->command('inspire')->hourly();
        // $schedule->command('sheet:sync-paket-umroh')
        //     ->dailyAt('10:00')
        //     ->timezone('Asia/Jakarta') // penting! tanpa ini, Laravel pakai timezone default app
        //     ->withoutOverlapping();

        // $schedule->command('jamaah:import-sheet')
        //     ->dailyAt('10:00')
        //     ->withoutOverlapping()
        //     ->appendOutputTo(storage_path('logs/jamaah-import.log'));

        $schedule->command('sheet:sync-paket-umroh')
            ->dailyAt('10:00')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping(30)
            ->appendOutputTo(storage_path('logs/sync-paket-umroh.log'))
            ->emailOutputOnFailure('admin@domain.com');

        $schedule->command('jamaah:import-sheet')
            ->dailyAt('06:00')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping(30)
            ->appendOutputTo(storage_path('logs/jamaah-import.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
