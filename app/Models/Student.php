<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'rude',
        'ci',
        'nombres',
        'apellidos',
        'nivel',
        'curso',
        'paralelo',
        'activo',
        'email',
        'telefono',
        'codigo',
        'usuario',
        'password_temp',
        'anio_ingreso',
        'mes_ingreso',
        'fecha_nac',
        'sexo',
        'user_id'
    ];

    protected $casts = [
        'fecha_nac' => 'date',
        'discapacidad' => 'boolean',
        'vulnerabilidad' => 'boolean',
        'idiomas' => 'array',
        'activo' => 'boolean'
    ];

    /**
     * Usuario del sistema asociado
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Líneas del centralizador (notas finales)
     */
    public function centralizadorLines()
    {
        return $this->hasMany(CentralizadorLine::class);
    }

    /**
     * Estados por año (promoción)
     */
    public function yearStatuses()
    {
        return $this->hasMany(StudentYearStatus::class);
    }

    /**
     * Historial de promociones
     */
    public function promociones()
    {
        return $this->hasMany(Promocion::class);
    }

    /**
     * Asistencias
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Incidentes de conducta
     */
    public function conductIncidents()
    {
        return $this->hasMany(ConductIncident::class);
    }

    /**
     * Cuenta financiera
     */
    public function studentAccount()
    {
        return $this->hasOne(StudentAccount::class);
    }

    /**
     * Scope: Solo estudiantes activos
     */
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope: Por nivel
     */
    public function scopeByNivel($query, string $nivel)
    {
        return $query->where('nivel', $nivel);
    }

    /**
     * Scope: Por curso/grado
     */
    public function scopeByCurso($query, int $curso)
    {
        return $query->where('curso', $curso);
    }

    /**
     * Obtiene nombre completo
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }

    /**
     * Verifica si está promovido en un año
     */
    public function isPromovido(int $year): bool
    {
        return $this->yearStatuses()
            ->where('year', $year)
            ->where('estado', 'PROMOVIDO')
            ->exists();
    }
}
