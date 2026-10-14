<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CentralizadorLine;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Acta;
use App\Models\ActaTermTotal;
use App\Services\GradeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class CentralizadorController extends Controller
{
    protected $gradeService;

    public function __construct(GradeService $gradeService)
    {
        $this->gradeService = $gradeService;
    }

    /**
     * GET /api/centralizador/course?year=&grado=&seccion=&term=
     * Ver centralizado por curso
     */
    public function byCourse(Request $req)
    {
        $this->authorize('viewAny', CentralizadorLine::class);

        $data = $req->validate([
            'year' => ['required','integer'],
            'grado' => ['required','string'],
            'seccion' => ['required','string'],
            'term' => ['required'],
        ]);

        $year = $data['year'];
        $grado = $data['grado'];
        $seccion = $data['seccion'];
        $term = $data['term'];

        $col = match((int)$term) {
            1 => 't1',
            2 => 't2',
            3 => 't3',
            default => 't1'
        };

        $lines = CentralizadorLine::where('year', $year)
            ->where('grado', $grado)
            ->where('seccion', $seccion)
            ->get();

        $studentIds = $lines->pluck('student_id')->unique()->values()->all();
        $materiaIds = $lines->pluck('materia_id')->unique()->values()->all();

        $students = Student::whereIn('id', $studentIds)
            ->select('id','nombres','apellidos')
            ->get();

        $materias = Subject::whereIn('id', $materiaIds)
            ->select('id','nombre as nombre','nivel')
            ->get();

        $celdas = $lines->map(function($l) use ($col) {
            return [
                'student_id' => $l->student_id,
                'subject_id' => $l->materia_id,
                't' => $l->{$col},
            ];
        })->values();

        $pa = $lines->map(function($l){
            return ['student_id' => $l->student_id, 'pa' => $l->pa];
        })->values();

        return response()->json([
            'alumnos' => $students,
            'materias' => $materias,
            'celdas' => $celdas,
            'pa' => $pa,
            'meta' => ['year'=>$year,'grado'=>$grado,'seccion'=>$seccion,'term'=>$term]
        ]);
    }

    /**
     * GET /api/centralizador/student/{student_id}?year=
     * Ver centralizado de un estudiante
     */
    public function byStudent($studentId, Request $req)
    {
        $this->authorize('viewAny', CentralizadorLine::class);

        $data = $req->validate(['year' => ['required','integer']]);
        $year = $data['year'];

        $student = Student::findOrFail($studentId);

        $lines = CentralizadorLine::where('year', $year)
            ->where('student_id', $studentId)
            ->with('materia')
            ->get();

        $result = $lines->map(function($l){
            return [
                'materia_id' => $l->materia_id,
                'materia_nombre' => optional($l->materia)->nombre,
                't1' => $l->t1,
                't2' => $l->t2,
                't3' => $l->t3,
                'pa' => $l->pa,
            ];
        });

        return response()->json([
            'student' => $student->only(['id', 'nombres', 'apellidos']),
            'year' => $year,
            'rows' => $result
        ]);
    }

    /**
     * POST /api/centralizador/calculate
     * Calcular/Recalcular centralizado para un scope especÃƒÂ­fico
     */
    public function calculate(Request $request)
    {
        $this->authorize('create', CentralizadorLine::class);

        $data = $request->validate([
            'year' => 'required|integer',
            'grado' => 'required|string',
            'seccion' => 'required|string',
            'nivel' => 'required|in:inicial,primaria,secundaria'
        ]);

        try {
            Log::info('Ã°Å¸â€œÅ  Calculando centralizado', $data);

            DB::beginTransaction();

            // Obtener todas las actas publicadas para este scope
            $actas = Acta::where('year', $data['year'])
                ->where('grado', $data['grado'])
                ->where('seccion', $data['seccion'])
                ->where('nivel', $data['nivel'])
                ->where('status', 'PUBLISHED')
                ->get();

            if ($actas->isEmpty()) {
                return response()->json([
                    'message' => 'No hay actas publicadas para este scope',
                    'data' => $data
                ], 404);
            }

            // Obtener estudiantes ÃƒÂºnicos de las actas
            $studentIds = [];
            foreach ($actas as $acta) {
                $termTotals = ActaTermTotal::where('acta_id', $acta->id)->pluck('student_id');
                $studentIds = array_merge($studentIds, $termTotals->toArray());
            }
            $studentIds = array_unique($studentIds);

            $materiasIds = $actas->pluck('materia_id')->unique();

            Log::info('Ã°Å¸â€œâ€¹ Scope encontrado', [
                'actas' => $actas->count(),
                'estudiantes' => count($studentIds),
                'materias' => $materiasIds->count()
            ]);

            // Procesar cada estudiante
            foreach ($studentIds as $studentId) {
                foreach ($materiasIds as $materiaId) {
                    // Buscar o crear lÃƒÂ­nea del centralizado
                    $line = CentralizadorLine::firstOrNew([
                        'year' => $data['year'],
                        'student_id' => $studentId,
                        'materia_id' => $materiaId,
                        'grado' => $data['grado'],
                        'seccion' => $data['seccion']
                    ]);

                    // Obtener TN de cada trimestre
                    foreach (['T1', 'T2', 'T3'] as $index => $term) {
                        $acta = $actas->where('term', $term)
                            ->where('materia_id', $materiaId)
                            ->first();

                        if ($acta) {
                            $termTotal = ActaTermTotal::where('acta_id', $acta->id)
                                ->where('student_id', $studentId)
                                ->first();

                            $column = 't' . ($index + 1);
                            $line->{$column} = $termTotal ? $termTotal->tn : null;
                        }
                    }

                    // Calcular PA (Promedio Anual)
                    $notas = array_filter([$line->t1, $line->t2, $line->t3], function($v) {
                        return $v !== null;
                    });

                    $line->pa = count($notas) > 0 ? round(array_sum($notas) / count($notas)) : null;

                    $line->save();
                }
            }

            DB::commit();

            Log::info('Ã¢Å“â€¦ Centralizado calculado exitosamente', [
                'lineas_procesadas' => count($studentIds) * $materiasIds->count()
            ]);

            return response()->json([
                'message' => 'Centralizado calculado exitosamente',
                'data' => [
                    'year' => $data['year'],
                    'grado' => $data['grado'],
                    'seccion' => $data['seccion'],
                    'estudiantes_procesados' => count($studentIds),
                    'materias_procesadas' => $materiasIds->count(),
                    'lineas_creadas' => count($studentIds) * $materiasIds->count()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ã¢ÂÅ’ Error al calcular centralizado: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error al calcular centralizado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/centralizador/export/pdf?year=&grado=&seccion=
     * Exportar centralizado a PDF
     */
    public function exportPDF(Request $request)
    {
        $this->authorize('viewAny', CentralizadorLine::class);

        $data = $request->validate([
            'year' => 'required|integer',
            'grado' => 'required|string',
            'seccion' => 'required|string'
        ]);

        $year = $data['year'];
        $grado = $data['grado'];
        $seccion = $data['seccion'];

        // Obtener datos del centralizado
        $lines = CentralizadorLine::where('year', $year)
            ->where('grado', $grado)
            ->where('seccion', $seccion)
            ->with(['student', 'materia'])
            ->get();

        if ($lines->isEmpty()) {
            return response()->json([
                'message' => 'No hay datos en el centralizado para estos parÃƒÂ¡metros'
            ], 404);
        }

        // Agrupar por estudiante
        $studentIds = $lines->pluck('student_id')->unique();
        $materiaIds = $lines->pluck('materia_id')->unique();

        $students = Student::whereIn('id', $studentIds)
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $materias = Subject::whereIn('id', $materiaIds)
            ->orderBy('nombre')
            ->get();

        // Crear matriz de datos
        $matriz = [];
        foreach ($students as $student) {
            $fila = [
                'student' => $student,
                'notas' => []
            ];

            foreach ($materias as $materia) {
                $line = $lines->where('student_id', $student->id)
                    ->where('materia_id', $materia->id)
                    ->first();

                $fila['notas'][$materia->id] = [
                    't1' => $line ? $line->t1 : '-',
                    't2' => $line ? $line->t2 : '-',
                    't3' => $line ? $line->t3 : '-',
                    'pa' => $line ? $line->pa : '-'
                ];
            }

            // Calcular PA general del estudiante
            $pas = $lines->where('student_id', $student->id)
                ->pluck('pa')
                ->filter()
                ->toArray();

            $fila['pa_general'] = count($pas) > 0 ? round(array_sum($pas) / count($pas)) : '-';

            $matriz[] = $fila;
        }

        // Generar PDF
        $pdf = Pdf::loadView('centralizador.pdf', [
            'year' => $year,
            'grado' => $grado,
            'seccion' => $seccion,
            'materias' => $materias,
            'matriz' => $matriz,
            'fecha_generacion' => now()->format('d/m/Y H:i')
        ]);

        $pdf->setPaper('legal', 'landscape');

        return $pdf->download("centralizador_{$year}_{$grado}_{$seccion}.pdf");
    }

    /**
     * POST /api/centralizador/validate
     * Validar que el centralizado estÃƒÂ¡ completo antes de calcular
     */
    public function validateCentralizador(Request $request)
    {
        $data = $request->validate([
            'year' => 'required|integer',
            'grado' => 'required|string',
            'seccion' => 'required|string',
            'nivel' => 'required|in:inicial,primaria,secundaria'
        ]);

        // Verificar actas publicadas
        $actasPublicadas = Acta::where('year', $data['year'])
            ->where('grado', $data['grado'])
            ->where('seccion', $data['seccion'])
            ->where('nivel', $data['nivel'])
            ->where('status', 'PUBLISHED')
            ->get();

        $actasPorTrimestre = $actasPublicadas->groupBy('term');

        $validacion = [
            'listo_para_calcular' => true,
            'advertencias' => [],
            'errores' => [],
            'resumen' => [
                'total_actas_publicadas' => $actasPublicadas->count(),
                'actas_t1' => $actasPorTrimestre->get('T1', collect())->count(),
                'actas_t2' => $actasPorTrimestre->get('T2', collect())->count(),
                'actas_t3' => $actasPorTrimestre->get('T3', collect())->count()
            ]
        ];

        // Validar que hay actas en cada trimestre
        foreach (['T1', 'T2', 'T3'] as $term) {
            $count = $actasPorTrimestre->get($term, collect())->count();
            if ($count === 0) {
                $validacion['advertencias'][] = "No hay actas publicadas para el trimestre {$term}";
            }
        }

        // Verificar que todas las materias tienen actas en todos los trimestres
        $materias = $actasPublicadas->pluck('materia_id')->unique();
        foreach ($materias as $materiaId) {
            foreach (['T1', 'T2', 'T3'] as $term) {
                $existe = $actasPublicadas->where('materia_id', $materiaId)
                    ->where('term', $term)
                    ->isNotEmpty();

                if (!$existe) {
                    $materia = Subject::find($materiaId);
                    $validacion['advertencias'][] = "Falta acta de {$materia->nombre} en {$term}";
                }
            }
        }

        if (count($validacion['advertencias']) > 0) {
            $validacion['listo_para_calcular'] = false;
        }

        return response()->json($validacion);
    }
}
