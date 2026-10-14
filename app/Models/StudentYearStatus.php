<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentYearStatus extends Model
{
    protected $fillable = [
        'student_id','year','nivel','grado','seccion',
        'estado','promedio_final','reprobadas','promedio_extra','decided_at','meta'
    ];

    protected $casts = [
        'promedio_final' => 'decimal:2',
        'promedio_extra' => 'decimal:2',
        'decided_at'     => 'datetime',
        'meta'           => 'array',
    ];

    public function student() {
        return $this->belongsTo(Student::class);
    }
}
