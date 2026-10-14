<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActaTermTotal extends Model
{
    use HasFactory;

    /**
     * Table associated with the model.
     * By convention Eloquent would use the plural snake_case name
     * `acta_term_totals` for this model. Adjust if your table name
     * is different.
     */
    protected $table = 'acta_term_totals';

    /**
     * Fillable attributes â€” keep minimal and extend later as needed.
     */
    protected $fillable = [
        'acta_id',
        'student_id',
        'term',
        'autoevaluacion',
        'pe',
        'tspe',
        'tn',
    ];

    /**
     * Relationship back to Acta
     */
    public function acta()
    {
        return $this->belongsTo(Acta::class, 'acta_id');
    }
}
