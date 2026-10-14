<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Period",
 *     type="object",
 *     required={"year"},
 *     @OA\Property(property="id", type="integer", example=10),
 *     @OA\Property(property="year", type="integer", example=2024),
 *     @OA\Property(
 *         property="code",
 *         type="string",
 *         enum={"T1","T2","T3"},
 *         nullable=true,
 *         example="T1"
 *     ),
 *     @OA\Property(property="inicio", type="string", format="date", nullable=true, example="2024-02-01"),
 *     @OA\Property(property="fin", type="string", format="date", nullable=true, example="2024-04-30"),
 *     @OA\Property(
 *         property="estado",
 *         type="string",
 *         enum={"ABIERTO","CERRADO"},
 *         nullable=true,
 *         example="ABIERTO"
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Period extends Model
{
    use HasFactory;

    protected $fillable = ['year', 'code', 'inicio', 'fin', 'estado'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Period $period) {
            // Default de estado si no viene
            if (empty($period->estado)) {
                $period->estado = 'ABIERTO';
            }

            // Si no envían code, lo generamos automáticamente como T1/T2/T3 por año
            if (empty($period->code)) {
                $year = $period->year ?? date('Y');

                // Traemos los códigos ya usados para ese año
                $used = Period::where('year', $year)
                    ->pluck('code')
                    ->filter()
                    ->toArray();

                $posibles = ['T1', 'T2', 'T3'];

                foreach ($posibles as $c) {
                    if (!in_array($c, $used)) {
                        $period->code = $c;
                        break;
                    }
                }

                // Si por alguna razón ya existen T1,T2,T3, generamos uno genérico
                if (empty($period->code)) {
                    $period->code = 'T' . (count($used) + 1);
                }
            }
        });
    }
}
