<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AuditService
{
    public static function log(string $action, string $target, ?string $ref = null, $before=null, $after=null): void
    {
        $user = auth()->user();
        DB::table('audit_events')->insert([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'action' => $action,
            'target' => $target,
            'ref' => $ref,
            'before' => $before ? json_encode($before) : null,
            'after'  => $after  ? json_encode($after)  : null,
            'ip' => request()->ip(),
            'ua' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
