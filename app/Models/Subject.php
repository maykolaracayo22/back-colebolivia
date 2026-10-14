<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Subject",
 *     type="object",
 *     required={"nombre"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Matem�tica"),
 *     @OA\Property(
 *         property="nivel",
 *         type="string",
 *         enum={"inicial","primaria","secundaria"},
 *         nullable=true,
 *         example="secundaria"
 *     ),
 *     @OA\Property(property="area", type="string", nullable=true, example="Ciencias Exactas"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Subject extends Model {
    use HasFactory;

    protected $fillable = ['nombre','nivel','area'];

    /**
     * Asignaciones de esta materia a secciones y docentes
     */
    public function assignments() { return $this->hasMany(Assignment::class); }

    public function grades() { return $this->hasMany(Grade::class); }

    /**
     * Obtener todos los docentes que enseñan esta materia
     */
    public function teachers()
    {
        return $this->hasManyThrough(Teacher::class, Assignment::class, 'subject_id', 'id', 'id', 'teacher_id');
    }
}
