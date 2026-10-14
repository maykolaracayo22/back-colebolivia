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
        \App\Console\Commands\ActasAutoLock::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Cierre preliminar el 30 de noviembre (o a diario la Ãºltima semana de Nov)
        $schedule->command('promos:run '.date('Y').' --phase=noviembre')->monthlyOn(30, '02:00');

        // RecuperaciÃ³n en diciembre, correr a diario del 20 al 31
        $schedule->command('promos:run '.date('Y').' --phase=diciembre')->cron('0 2 20-31 12 *');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
