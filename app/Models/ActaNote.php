<?php
// app/Models/ActaNote.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="ActaNote",
 *     type="object",
 *     required={"acta_id","evaluation_id","student_id","tipo"},
 *     @OA\Property(property="id", type="integer", example=1200),
 *     @OA\Property(property="acta_id", type="integer", example=5),
 *     @OA\Property(property="evaluation_id", type="integer", example=20),
 *     @OA\Property(property="student_id", type="integer", example=15),
 *     @OA\Property(
 *         property="tipo",
 *         type="string",
 *         enum={"NUM","QUAL"},
 *         example="NUM"
 *     ),
 *     @OA\Property(property="valor", type="number", format="float", nullable=true, example=85),
 *     @OA\Property(property="valor_qual", type="string", nullable=true, example="A"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class ActaNote extends Model
{
    use HasFactory;

    protected $table = 'acta_notes';

    // Soporta ES/EN
    protected $fillable = [
        'acta_id',
        'evaluation_id',
        'student_id',
        'tipo', 'valor', 'valor_qual',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    public function acta()
    {
        return $this->belongsTo(Acta::class, 'acta_id');
    }

    public function evaluation()
    {
        return $this->belongsTo(ActaEvaluation::class, 'evaluation_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
