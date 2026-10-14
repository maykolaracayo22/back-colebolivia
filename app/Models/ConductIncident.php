<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ConductIncident",
 *     type="object",
 *     required={"student_id","tipo","descripcion","fecha","severidad"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="student_id", type="integer", example=123),
 *     @OA\Property(property="reported_by", type="integer", example=5),
 *     @OA\Property(property="tipo", type="string", enum={"positiva","negativa"}, example="negativa"),
 *     @OA\Property(property="descripcion", type="string", example="Interrumpió la clase reiteradamente"),
 *     @OA\Property(property="fecha", type="string", format="date", example="2025-03-01"),
 *     @OA\Property(property="severidad", type="string", enum={"leve","moderada","grave"}, example="moderada"),
 *     @OA\Property(property="acciones_tomadas", type="string", nullable=true, example="Llamado de atención"),
 *     @OA\Property(property="aprobado_por", type="integer", nullable=true, example=2),
 *     @OA\Property(property="aprobado_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class ConductIncident extends Model {
    use HasFactory;

    protected $fillable = [
        'student_id', 'reported_by', 'tipo', 'descripcion', 'fecha', 'severidad',
        'acciones_tomadas', 'aprobado_por', 'aprobado_at'
    ];

    protected $casts = [
        'fecha' => 'date',
        'aprobado_at' => 'datetime',
    ];

    public function student() {
        return $this->belongsTo(Student::class);
    }

    public function reportedBy() {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function aprobadoPor() {
        return $this->belongsTo(User::class, 'aprobado_por');
    }
}
