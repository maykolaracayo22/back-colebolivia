<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Acta;

class ActasAutoLock extends Command
{
    protected $signature = 'actas:auto-lock';
    protected $description = 'Cierra (LOCK) actas en DRAFT cuya ventana (window_ends_at) ya expirÃ³';

    public function handle()
    {
        $now = now();
        $this->info('Buscando actas DRAFT con window_ends_at < '.$now->toDateTimeString());

        $actas = Acta::where('status','DRAFT')
            ->whereNotNull('window_ends_at')
            ->where('window_ends_at','<', $now)
            ->get();

        $count = 0;
        foreach ($actas as $acta) {
            $acta->status = 'LOCKED';
            $acta->locked_at = $now;
            // locked_by can't be set reliably in a CLI; leave null or set to system (if exists)
            $acta->etag = (string) \Illuminate\Support\Str::uuid();
            $acta->save();
            $count++;
            $this->line("Locked acta #{$acta->id} ({$acta->ref})");
        }

        $this->info("Total actas bloqueadas: {$count}");
        return 0;
    }
}
