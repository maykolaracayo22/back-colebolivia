<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Grade",
 *     type="object",
 *     required={"year","student_id","subject_id","period_code","puntaje"},
 *     @OA\Property(property="id", type="integer", example=100),
 *     @OA\Property(property="year", type="integer", example=2024),
 *     @OA\Property(
 *         property="period_code",
 *         type="string",
 *         enum={"T1","T2","T3"},
 *         example="T1"
 *     ),
 *     @OA\Property(property="student_id", type="integer", example=15),
 *     @OA\Property(property="subject_id", type="integer", example=3),
 *     @OA\Property(property="puntaje", type="number", format="float", example=86.5),
 *     @OA\Property(property="peso", type="integer", nullable=true, example=100),
 *     @OA\Property(property="publicado", type="boolean", nullable=true, example=false),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Grade extends Model {
    use HasFactory;

    protected $fillable = [
        'year','student_id','subject_id','peso','period_code','puntaje','publicado'
    ];

    public function student() { return $this->belongsTo(Student::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
}
