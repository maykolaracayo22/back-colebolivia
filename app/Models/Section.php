<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model {
    use HasFactory;

    protected $fillable = [
        'year','nivel','curso','paralelo','turno','aula','capacidad','tutor_id','estado'
    ];

    public function tutor() { return $this->belongsTo(Teacher::class, 'tutor_id'); }
    public function enrollments() { return $this->hasMany(Enrollment::class); }

    /**
     * Asignaciones de materias y docentes a esta secciÃ³n
     */
    public function assignments() { return $this->hasMany(Assignment::class); }

    /**
     * Obtener todas las materias asignadas a esta secciÃ³n
     */
    public function subjects()
    {
        return $this->hasManyThrough(Subject::class, Assignment::class, 'section_id', 'id', 'id', 'subject_id');
    }

    /**
     * Obtener todos los docentes asignados a esta secciÃ³n
     */
    public function teachers()
    {
        return $this->hasManyThrough(Teacher::class, Assignment::class, 'section_id', 'id', 'id', 'teacher_id');
    }
}
