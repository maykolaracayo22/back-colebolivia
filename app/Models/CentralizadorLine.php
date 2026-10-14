<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="CentralizadorLine",
 *     type="object",
 *     title="Línea de Centralizador",
 *     required={"year","nivel","grado","seccion","materia_id","student_id"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="year", type="integer", example=2024),
 *     @OA\Property(property="nivel", type="string", example="primaria"),
 *     @OA\Property(property="grado", type="string", example="1"),
 *     @OA\Property(property="seccion", type="string", example="A"),
 *     @OA\Property(property="section_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="materia_id", type="integer", example=1),
 *     @OA\Property(property="student_id", type="integer", example=1),
 *     @OA\Property(property="t1", type="integer", nullable=true, example=85),
 *     @OA\Property(property="t2", type="integer", nullable=true, example=90),
 *     @OA\Property(property="t3", type="integer", nullable=true, example=88),
 *     @OA\Property(property="pa", type="integer", nullable=true, example=88, description="Promedio Anual"),
 *     @OA\Property(property="status", type="string", example="DRAFT"),
 *     @OA\Property(property="published_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="published_by", type="integer", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class CentralizadorLine extends Model
{
    use HasFactory;

    protected $table = 'centralizador_lines';

    protected $fillable = [
        'year', 'nivel', 'grado', 'seccion', 'section_id',
        'materia_id', 'student_id',
        't1', 't2', 't3', 'pa',
        'status', 'published_at', 'published_by',
    ];

    protected $casts = [
        'year' => 'integer',
        't1' => 'integer',
        't2' => 'integer',
        't3' => 'integer',
        'pa' => 'integer',
        'published_at' => 'datetime',
    ];

    public function student() { return $this->belongsTo(Student::class); }
    public function subject() { return $this->belongsTo(Materia::class, 'materia_id'); }
    public function section() { return $this->belongsTo(Section::class); }
    public function publisher() { return $this->belongsTo(User::class, 'published_by'); }

    public function recalculatePA(): void
    {
        if ($this->t1 !== null && $this->t2 !== null && $this->t3 !== null) {
            $this->pa = round(($this->t1 + $this->t2 + $this->t3) / 3);
            $this->save();
        }
    }

    public function scopeYear($query, $year) { return $query->where('year', $year); }
    public function scopeByCourse($query, $nivel, $grado, $seccion) {
        return $query->where('nivel', $nivel)->where('grado', $grado)->where('seccion', $seccion);
    }
    public function scopeByStudent($query, $studentId) { return $query->where('student_id', $studentId); }
    public function scopeByMateria($query, $materiaId) { return $query->where('materia_id', $materiaId); }
    public function scopeByStatus($query, $status) { return $query->where('status', $status); }
}
