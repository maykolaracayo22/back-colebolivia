<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model {
    use HasFactory;

    protected $fillable = [
        'ci','nombres','apellidos','email','telefono',
        'rda_numero','rda_estado','rda_vencimiento','estado',
        'usuario','anio_ingreso','mes_ingreso','password_temp',
        'nivel_formacion','especialidad','turno','fecha_ingreso','activo',
        'user_id'
    ];

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }
    public function sectionsTutoria() { return $this->hasMany(Section::class, 'tutor_id'); }

    public function user() { return $this->hasOne(\App\Models\User::class, 'teacher_id'); }

    // Niveles (Inicial / Primaria / Secundaria) - many-to-many
    public function levels() { return $this->belongsToMany(Level::class, 'level_teacher'); }
}
