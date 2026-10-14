<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Attendance",
 *     type="object",
 *     required={"student_id","section_id","subject_id","teacher_id","fecha","estado"},
 *     @OA\Property(property="id", type="integer", example=500),
 *     @OA\Property(property="student_id", type="integer", example=15),
 *     @OA\Property(property="section_id", type="integer", example=4),
 *     @OA\Property(property="subject_id", type="integer", example=3),
 *     @OA\Property(property="teacher_id", type="integer", example=12),
 *     @OA\Property(property="fecha", type="string", format="date", example="2024-03-15"),
 *     @OA\Property(
 *         property="estado",
 *         type="string",
 *         enum={"presente","ausente","tardanza","justificado"},
 *         example="presente"
 *     ),
 *     @OA\Property(property="observaciones", type="string", nullable=true, example="Llegó 5 minutos tarde"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Attendance extends Model {
    use HasFactory;

    protected $fillable = [
        'student_id', 'section_id', 'subject_id', 'teacher_id', 'fecha', 'estado', 'observaciones'
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function student() {
        return $this->belongsTo(Student::class);
    }

    public function section() {
        return $this->belongsTo(Section::class);
    }

    public function subject() {
        return $this->belongsTo(Subject::class);
    }

    public function teacher() {
        return $this->belongsTo(Teacher::class);
    }
}
