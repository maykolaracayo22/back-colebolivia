<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Curso/Grado de escolaridad según Ley 070 Bolivia
 * Ejemplos: 1° Inicial, 2° Inicial, 1° Primaria, ... 6° Secundaria
 */
class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',       // Ej: "1° de Primaria", "2° de Secundaria"
        'codigo',       // Ej: "1P", "2S", "1I"
        'nivel',        // inicial, primaria, secundaria
        'grado',        // 1, 2, 3... (número de grado)
        'orden',        // Para ordenar en listados
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'grado' => 'integer',
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    // Relación con secciones usando nivel y grado (no course_id)
    // La tabla sections usa 'curso' (string) y 'nivel'
    public function sections()
    {
        return $this->hasMany(Section::class, 'curso', 'grado')
            ->where('nivel', $this->nivel);
    }

    // Método para contar secciones manualmente (más preciso)
    public function getSectionsCountAttribute(): int
    {
        return Section::where('nivel', $this->nivel)
            ->where('curso', (string) $this->grado)
            ->count();
    }

    // Scope para filtrar por nivel
    public function scopeNivel($query, $nivel)
    {
        return $query->where('nivel', $nivel);
    }

    // Scope para solo activos
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Ordenar por nivel y grado
    public function scopeOrdenado($query)
    {
        return $query->orderByRaw("FIELD(nivel, 'inicial', 'primaria', 'secundaria')")
                     ->orderBy('grado');
    }
}
