<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Assignment",
 *     type="object",
 *     required={"year","section_id","subject_id","teacher_id"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="year", type="integer", example=2024),
 *     @OA\Property(property="gestion", type="string", example="2024"),
 *     @OA\Property(property="section_id", type="integer", example=5),
 *     @OA\Property(property="subject_id", type="integer", example=3),
 *     @OA\Property(property="teacher_id", type="integer", example=10),
 *     @OA\Property(property="dia", type="string", nullable=true, example="Lunes"),
 *     @OA\Property(property="inicio", type="string", format="time", nullable=true, example="08:00"),
 *     @OA\Property(property="fin", type="string", format="time", nullable=true, example="09:30"),
 *     @OA\Property(property="aula", type="string", nullable=true, example="Aula 101"),
 *     @OA\Property(property="titular", type="boolean", nullable=true, example=true),
 *     @OA\Property(property="estado", type="string", enum={"ACTIVO","INACTIVO"}, example="ACTIVO"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Assignment extends Model {
    use HasFactory;

    protected $fillable = ['year','gestion','section_id','subject_id','teacher_id','dia','inicio','fin','aula','titular','estado'];
    protected $casts = ['titular' => 'boolean'];

    public function section() { return $this->belongsTo(Section::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function teacher() { return $this->belongsTo(Teacher::class); }
    
    public function scopeActive($query) { return $query->where('estado', 'ACTIVO'); }
    public function scopeForYear($query, $year) { return $query->where('year', $year); }
    public function scopeForTeacher($query, $teacherId) { return $query->where('teacher_id', $teacherId); }
    public function scopeForSection($query, $sectionId) { return $query->where('section_id', $sectionId); }
}
