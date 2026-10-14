<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable;

class AuditEvent extends Model
{
    use HasFactory;

    protected $table = 'audit_events';

    protected $fillable = [
        'user_id',
        'user_nombre',
        'accion',
        'target',
        'ref',
        'before',
        'after',
        'ts',
        'ip',
        'ua',
    ];

    protected $casts = [
        'before' => 'array',
        'after'  => 'array',
        'ts'     => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Helper estático para registrar eventos de auditoría
     *
     * Ejemplo de uso:
     * AuditEvent::log('preinscription.approve', [
     *     'target' => 'preinscriptions',
     *     'ref'    => $pre->id,
     *     'before' => ['estado' => 'pendiente'],
     *     'after'  => ['estado' => 'aprobado'],
     * ]);
     *
     * @param  string                    $accion
     * @param  array                     $extra  ['target','ref','before','after']
     * @param  Authenticatable|null      $user   (opcional, por defecto usa request()->user())
     * @return static
     */
    public static function log(string $accion, array $extra = [], ?Authenticatable $user = null)
    {
        $request = request();

        $user = $user ?: ($request ? $request->user() : null);

        return self::create([
            'accion'      => $accion,
            'user_id'     => $user?->id,
            'user_nombre' => $user?->name ?? $user?->email,
            'target'      => $extra['target'] ?? null,
            'ref'         => $extra['ref'] ?? null,
            'before'      => $extra['before'] ?? null,
            'after'       => $extra['after'] ?? null,
            'ip'          => $request?->ip(),
            'ua'          => $request?->userAgent(),
            'ts'          => now(),
        ]);
    }
}
