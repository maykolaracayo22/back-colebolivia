<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *   schema="Promocion",
 *   type="object",
 *   required={"student_id","gestion_origen","gestion_destino","nivel_origen","curso_origen","paralelo_origen","nivel_destino","curso_destino","paralelo_destino","estado"},
 *   @OA\Property(property="id", type="integer", example=123),
 *   @OA\Property(property="student_id", type="integer", example=45),
 *   @OA\Property(property="gestion_origen", type="integer", example=2024),
 *   @OA\Property(property="gestion_destino", type="integer", example=2025),
 *   @OA\Property(property="nivel_origen", type="string", example="primaria"),
 *   @OA\Property(property="curso_origen", type="integer", example=6),
 *   @OA\Property(property="paralelo_origen", type="string", example="B"),
 *   @OA\Property(property="nivel_destino", type="string", example="secundaria"),
 *   @OA\Property(property="curso_destino", type="integer", example=1),
 *   @OA\Property(property="paralelo_destino", type="string", example="A"),
 *   @OA\Property(property="estado", type="string", enum={"pendiente","aprobado","rechazado"}, example="pendiente"),
 *   @OA\Property(property="observaciones", type="string", nullable=true),
 *   @OA\Property(property="aprobado_por", type="integer", nullable=true),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Promocion extends Model
{
    use HasFactory;

    protected $table = 'promociones';

    protected $fillable = [
        'student_id',
        'gestion_origen',
        'gestion_destino',
        'nivel_origen',
        'curso_origen',
        'paralelo_origen',
        'nivel_destino',
        'curso_destino',
        'paralelo_destino',
        'estado',
        'observaciones',
        'aprobado_por',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }
}
