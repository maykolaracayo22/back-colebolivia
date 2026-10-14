<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Preinscription",
 *     type="object",
 *     required={"nivel","grado","nombres","apellidos","sexo","fecha_nac","tutor_nombre","tutor_parentesco"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="codigo_preinscripcion", type="string", example="PRE-AB12CD34"),
 *     @OA\Property(property="nivel", type="string", enum={"inicial","primaria","secundaria"}),
 *     @OA\Property(property="grado", type="integer", example=6),
 *     @OA\Property(property="nombres", type="string", example="María"),
 *     @OA\Property(property="apellidos", type="string", example="García López"),
 *     @OA\Property(property="sexo", type="string", enum={"M","F"}),
 *     @OA\Property(property="fecha_nac", type="string", format="date", example="2015-03-10"),
 *     @OA\Property(property="ci", type="string", nullable=true),
 *     @OA\Property(property="nacionalidad", type="string", nullable=true),
 *     @OA\Property(property="dep_nac", type="string", nullable=true),
 *     @OA\Property(property="muni_nac", type="string", nullable=true),
 *     @OA\Property(property="direccion", type="string", nullable=true),
 *     @OA\Property(property="telefono", type="string", nullable=true),
 *     @OA\Property(property="email", type="string", format="email", nullable=true),
 *     @OA\Property(property="pueblo_indigena", type="string", nullable=true),
 *     @OA\Property(property="discapacidad", type="boolean"),
 *     @OA\Property(property="idiomas", type="array", @OA\Items(type="string"), nullable=true),
 *     @OA\Property(property="vulnerabilidad", type="boolean"),
 *     @OA\Property(property="tutor_nombre", type="string"),
 *     @OA\Property(property="tutor_ci", type="string", nullable=true),
 *     @OA\Property(property="tutor_parentesco", type="string"),
 *     @OA\Property(property="tutor_telefono", type="string", nullable=true),
 *     @OA\Property(property="comprobante_path", type="string", nullable=true),
 *     @OA\Property(property="estado", type="string", example="pendiente"),
 *     @OA\Property(property="observaciones", type="string", nullable=true),
 *     @OA\Property(property="aprobado_por", type="integer", nullable=true),
 *     @OA\Property(property="aprobado_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Preinscription extends Model {
    use HasFactory;

    protected $fillable = [
        'codigo_preinscripcion', 'nivel', 'grado', 'nombres', 'apellidos', 'sexo', 'fecha_nac',
        'ci', 'nacionalidad', 'dep_nac', 'muni_nac', 'direccion', 'telefono', 'email',
        'pueblo_indigena', 'discapacidad', 'idiomas', 'vulnerabilidad',
        'tutor_nombre', 'tutor_ci', 'tutor_parentesco', 'tutor_telefono',
        'comprobante_path', 'estado', 'observaciones', 'aprobado_por', 'aprobado_at'
    ];

    protected $casts = [
        'fecha_nac' => 'date',
        'discapacidad' => 'boolean',
        'vulnerabilidad' => 'boolean',
        'idiomas' => 'array',
        'aprobado_at' => 'datetime',
    ];

    public function aprobadoPor() {
        return $this->belongsTo(User::class, 'aprobado_por');
    }
}
