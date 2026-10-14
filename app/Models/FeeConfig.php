<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="FeeConfig",
 *     type="object",
 *     required={"year","concepto","monto"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="year", type="integer", example=2025),
 *     @OA\Property(property="nivel", type="string", nullable=true, enum={"inicial","primaria","secundaria"}),
 *     @OA\Property(property="grado", type="integer", nullable=true, example=6),
 *     @OA\Property(property="concepto", type="string", example="Pensión mensual"),
 *     @OA\Property(property="monto", type="number", format="float", example=150.50),
 *     @OA\Property(property="fecha_vencimiento", type="string", format="date", nullable=true, example="2025-03-10"),
 *     @OA\Property(property="obligatorio", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class FeeConfig extends Model {
    use HasFactory;

    protected $fillable = [
        'year', 'nivel', 'grado', 'concepto', 'monto', 'fecha_vencimiento', 'obligatorio'
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'obligatorio' => 'boolean',
    ];

    public function studentFees() {
        return $this->hasMany(StudentFee::class);
    }
}
