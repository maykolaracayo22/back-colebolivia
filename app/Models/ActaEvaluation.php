<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="ActaEvaluation",
 *     type="object",
 *     required={"acta_id","nombre","peso"},
 *     @OA\Property(property="id", type="integer", example=20),
 *     @OA\Property(property="acta_id", type="integer", example=5),
 *     @OA\Property(property="nombre", type="string", example="Examen Trimestral"),
 *     @OA\Property(property="peso", type="number", format="float", example=40),
 *     @OA\Property(property="fecha", type="string", format="date", nullable=true, example="2024-03-10"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class ActaEvaluation extends Model
{
    use HasFactory;

    protected $table = 'acta_evaluations';

    // SOLO columnas que existen en la BD
    protected $fillable = [
        'acta_id',
        'nombre',
        'peso',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function acta()
    {
        return $this->belongsTo(Acta::class, 'acta_id');
    }

    public function notes()
    {
        // evaluation_id es la FK en acta_notes
        return $this->hasMany(ActaNote::class, 'evaluation_id');
    }
}
