<?php

namespace App\Services;

use App\Models\Student;
use App\Models\CentralizadorLine;
use App\Models\StudentYearStatus;
use App\Models\Promocion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Promoción Automática
 *
 * Gestiona la promoción de estudiantes entre cursos según reglamento boliviano:
 * - PA (Promedio Anual) ≥ 51 en TODAS las materias → PROMOVIDO
 * - PA < 51 en alguna materia → RETENIDO
 * - 6º Primaria → 1º Secundaria
 * - 6º Secundaria → EGRESADO
 */
class PromotionService
{
    /**
     * Ejecuta proceso de promoción para un año
     *
     * @param int $year Año de gestión (ej: 2025)
     * @param string $phase Fase: 'noviembre' o 'diciembre'
     * @param bool $dry Si es true, solo simula (no guarda cambios)
     * @return array Resumen de resultados
     */
    public function runForYear(int $year, string $phase = 'noviembre', bool $dry = false): array
    {
        Log::info("🎓 Iniciando promoción", ['year' => $year, 'phase' => $phase, 'dry' => $dry]);

        $resultados = [
            'year' => $year,
            'phase' => $phase,
            'dry' => $dry,
            'timestamp' => now()->toDateTimeString(),
            'procesados' => 0,
            'promovidos' => 0,
            'retenidos' => 0,
            'egresados' => 0,
            'errores' => 0,
            'detalles' => []
        ];

        // Obtener todos los estudiantes activos del año
        $estudiantes = Student::where('activo', true)
            ->whereHas('centralizadorLines', function($q) use ($year) {
                $q->where('year', $year);
            })
            ->get();

        if ($estudiantes->isEmpty()) {
            $resultados['mensaje'] = 'No se encontraron estudiantes activos para procesar';
            return $resultados;
        }

        DB::beginTransaction();

        try {
            foreach ($estudiantes as $estudiante) {
                $resultado = $this->evaluarEstudiante($estudiante, $year, $phase, $dry);

                $resultados['procesados']++;
                $resultados['detalles'][] = $resultado;

                // Contadores
                if ($resultado['decision'] === 'PROMOVIDO') {
                    $resultados['promovidos']++;
                } elseif ($resultado['decision'] === 'RETENIDO') {
                    $resultados['retenidos']++;
                } elseif ($resultado['decision'] === 'EGRESADO') {
                    $resultados['egresados']++;
                } elseif (isset($resultado['error'])) {
                    $resultados['errores']++;
                }
            }

            if ($dry) {
                DB::rollBack();
                $resultados['mensaje'] = '✅ Simulación completada (sin cambios en BD)';
            } else {
                DB::commit();
                $resultados['mensaje'] = '✅ Promoción ejecutada exitosamente';
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error en proceso de promoción', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $resultados['error_general'] = $e->getMessage();
            $resultados['mensaje'] = '❌ Error en el proceso de promoción';
        }

        return $resultados;
    }

    /**
     * Evalúa un estudiante individual y determina su promoción
     */
    protected function evaluarEstudiante(Student $estudiante, int $year, string $phase, bool $dry): array
    {
        $resultado = [
            'student_id' => $estudiante->id,
            'nombres' => $estudiante->nombres . ' ' . $estudiante->apellidos,
            'nivel_actual' => $estudiante->nivel,
            'curso_actual' => $estudiante->curso,
            'paralelo_actual' => $estudiante->paralelo
        ];

        try {
            // 1. Obtener notas del centralizador
            $lineas = CentralizadorLine::where('student_id', $estudiante->id)
                ->where('year', $year)
                ->get();

            if ($lineas->isEmpty()) {
                $resultado['decision'] = 'SIN_DATOS';
                $resultado['observacion'] = 'No tiene notas en centralizador';
                return $resultado;
            }

            // 2. Verificar que todas las materias tengan PA
            $materiasSinPA = $lineas->filter(fn($l) => is_null($l->pa))->count();
            if ($materiasSinPA > 0) {
                $resultado['decision'] = 'INCOMPLETO';
                $resultado['observacion'] = "Faltan {$materiasSinPA} materias sin PA calculado";
                return $resultado;
            }

            // 3. Calcular PA promedio y contar reprobadas
            $paPromedio = $lineas->avg('pa');
            $materiasReprobadas = $lineas->filter(fn($l) => $l->pa < 51)->count();
            $totalMaterias = $lineas->count();

            $resultado['pa_promedio'] = round($paPromedio, 2);
            $resultado['total_materias'] = $totalMaterias;
            $resultado['reprobadas'] = $materiasReprobadas;

            // 4. Determinar decisión de promoción
            $decision = $this->determinarDecision(
                $estudiante,
                $materiasReprobadas,
                $paPromedio
            );

            $resultado['decision'] = $decision['estado'];
            $resultado['observacion'] = $decision['observacion'];
            $resultado['nivel_destino'] = $decision['nivel_destino'] ?? null;
            $resultado['curso_destino'] = $decision['curso_destino'] ?? null;

            // 5. Guardar en BD si no es dry run
            if (!$dry) {
                $this->guardarPromocion($estudiante, $year, $decision, $paPromedio, $materiasReprobadas);
            }

        } catch (\Exception $e) {
            $resultado['error'] = $e->getMessage();
            $resultado['decision'] = 'ERROR';
            Log::error('Error evaluando estudiante', [
                'student_id' => $estudiante->id,
                'error' => $e->getMessage()
            ]);
        }

        return $resultado;
    }

    /**
     * Determina la decisión de promoción según reglas bolivianas
     */
    protected function determinarDecision(Student $estudiante, int $reprobadas, float $paPromedio): array
    {
        $nivelActual = strtolower($estudiante->nivel);
        $cursoActual = (int) $estudiante->curso;

        // REGLA 1: Si tiene alguna materia reprobada (PA < 51) → RETENIDO
        if ($reprobadas > 0) {
            return [
                'estado' => 'RETENIDO',
                'observacion' => "Reprobó {$reprobadas} materia(s) con PA < 51",
                'nivel_destino' => $nivelActual,
                'curso_destino' => $cursoActual // Se queda en el mismo curso
            ];
        }

        // REGLA 2: Todas las materias aprobadas (PA ≥ 51)

        // Caso especial: 6º Secundaria → EGRESADO
        if ($nivelActual === 'secundaria' && $cursoActual == 6) {
            return [
                'estado' => 'EGRESADO',
                'observacion' => 'Culminó 6º Secundaria exitosamente',
                'nivel_destino' => null,
                'curso_destino' => null
            ];
        }

        // Caso especial: 6º Primaria → 1º Secundaria
        if ($nivelActual === 'primaria' && $cursoActual == 6) {
            return [
                'estado' => 'PROMOVIDO',
                'observacion' => 'Promovido de Primaria a Secundaria',
                'nivel_destino' => 'secundaria',
                'curso_destino' => 1
            ];
        }

        // Caso normal: Promover al siguiente curso en el mismo nivel
        return [
            'estado' => 'PROMOVIDO',
            'observacion' => "Promovido a {$cursoActual}° → " . ($cursoActual + 1) . "°",
            'nivel_destino' => $nivelActual,
            'curso_destino' => $cursoActual + 1
        ];
    }

    /**
     * Guarda los registros de promoción en BD
     */
    protected function guardarPromocion(
        Student $estudiante,
        int $year,
        array $decision,
        float $paPromedio,
        int $reprobadas
    ): void {
        // 1. Crear/Actualizar StudentYearStatus
        StudentYearStatus::updateOrCreate(
            [
                'student_id' => $estudiante->id,
                'year' => $year
            ],
            [
                'nivel' => $estudiante->nivel,
                'grado' => $estudiante->curso,
                'seccion' => $estudiante->paralelo,
                'estado' => $decision['estado'],
                'promedio_final' => $paPromedio,
                'reprobadas' => $reprobadas,
                'decided_at' => now(),
                'meta' => [
                    'observacion' => $decision['observacion'],
                    'nivel_destino' => $decision['nivel_destino'] ?? null,
                    'curso_destino' => $decision['curso_destino'] ?? null
                ]
            ]
        );

        // 2. Crear registro en Promocion si es PROMOVIDO o EGRESADO
        if (in_array($decision['estado'], ['PROMOVIDO', 'EGRESADO'])) {
            Promocion::create([
                'student_id' => $estudiante->id,
                'gestion_origen' => $year,
                'gestion_destino' => $year + 1,
                'nivel_origen' => $estudiante->nivel,
                'curso_origen' => $estudiante->curso,
                'paralelo_origen' => $estudiante->paralelo,
                'nivel_destino' => $decision['nivel_destino'],
                'curso_destino' => $decision['curso_destino'],
                'paralelo_destino' => null, // Se asigna manualmente después
                'estado' => $decision['estado'],
                'observaciones' => $decision['observacion'],
                'aprobado_por' => auth()->id() ?? 1 // Sistema automático
            ]);
        }

        // 3. Si es PROMOVIDO, actualizar datos del estudiante para siguiente gestión
        if ($decision['estado'] === 'PROMOVIDO') {
            $estudiante->update([
                'nivel' => $decision['nivel_destino'],
                'curso' => $decision['curso_destino'],
                // paralelo se asigna manualmente después
            ]);
        }

        // 4. Si es EGRESADO, marcar como inactivo
        if ($decision['estado'] === 'EGRESADO') {
            $estudiante->update([
                'activo' => false
            ]);
        }

        Log::info('✅ Promoción guardada', [
            'student_id' => $estudiante->id,
            'decision' => $decision['estado'],
            'destino' => $decision['nivel_destino'] . ' ' . $decision['curso_destino']
        ]);
    }

    /**
     * Obtiene resumen de promoción de un estudiante
     */
    public function resumenPromocion(int $studentId, int $year): array
    {
        $estudiante = Student::findOrFail($studentId);

        // Obtener status del año
        $status = StudentYearStatus::where('student_id', $studentId)
            ->where('year', $year)
            ->first();

        // Obtener líneas del centralizador
        $lineas = CentralizadorLine::where('student_id', $studentId)
            ->where('year', $year)
            ->with('subject')
            ->get();

        // Obtener promoción si existe
        $promocion = Promocion::where('student_id', $studentId)
            ->where('gestion_origen', $year)
            ->first();

        return [
            'estudiante' => [
                'id' => $estudiante->id,
                'nombres' => $estudiante->nombres . ' ' . $estudiante->apellidos,
                'nivel' => $estudiante->nivel,
                'curso' => $estudiante->curso,
                'paralelo' => $estudiante->paralelo
            ],
            'status' => $status ? [
                'estado' => $status->estado,
                'promedio_final' => $status->promedio_final,
                'reprobadas' => $status->reprobadas,
                'observacion' => $status->meta['observacion'] ?? null,
                'decidido_el' => $status->decided_at?->format('Y-m-d H:i:s')
            ] : null,
            'materias' => $lineas->map(fn($l) => [
                'materia' => $l->subject->nombre ?? 'N/A',
                't1' => $l->t1,
                't2' => $l->t2,
                't3' => $l->t3,
                'pa' => $l->pa,
                'aprobada' => $l->pa >= 51
            ])->toArray(),
            'promocion' => $promocion ? [
                'origen' => $promocion->nivel_origen . ' ' . $promocion->curso_origen,
                'destino' => $promocion->nivel_destino . ' ' . $promocion->curso_destino,
                'estado' => $promocion->estado,
                'observaciones' => $promocion->observaciones
            ] : null
        ];
    }

    /**
     * Revierte una promoción (uso administrativo)
     */
    public function revertirPromocion(int $studentId, int $year): bool
    {
        DB::beginTransaction();

        try {
            // Eliminar status
            StudentYearStatus::where('student_id', $studentId)
                ->where('year', $year)
                ->delete();

            // Eliminar promoción
            Promocion::where('student_id', $studentId)
                ->where('gestion_origen', $year)
                ->delete();

            DB::commit();
            Log::info('♻️ Promoción revertida', ['student_id' => $studentId, 'year' => $year]);
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error revirtiendo promoción', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
