<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PromotionService;

class RunPromotions extends Command
{
    protected $signature = 'promos:run {year?} {--phase=noviembre} {--dry}';
    protected $description = 'Ejecuta la promociÃ³n automÃ¡tica por aÃ±o (fase: noviembre o diciembre)';

    public function handle(PromotionService $svc)
    {
        $year = (int) ($this->argument('year') ?? date('Y'));
        $phase = $this->option('phase'); // noviembre|diciembre
        $dry = (bool) $this->option('dry');

        $this->info("Promociones {$year} (fase: {$phase}) ".($dry?'[DRY RUN]':''));
        $res = $svc->runForYear($year, $phase, $dry);
        $this->info("Actualizados: {$res['updated']}");
        return self::SUCCESS;
    }
}
