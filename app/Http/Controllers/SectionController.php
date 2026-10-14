<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    // Capacidades máximas según normativa boliviana Ley 070 Avelino Siñani - Elizardo Pérez
    private const CAPACIDAD_MAXIMA = [
        'inicial'    => 25,
        'primaria'   => 30,
        'secundaria' => 35,
    ];

    // LISTAR SECCIONES CON FILTROS (incluye tutor y conteo de estudiantes)
    public function index(Request $req)
    {
        $q = Section::query()
            ->with('tutor:id,nombres,apellidos,email') // Eager load tutor
            ->withCount(['enrollments as estudiantes_count' => function ($query) {
                $query->whereIn('estado', ['INSCRITO', 'PREINSCRITO']);
            }])
            ->when($req->filled('year'),   fn ($x) => $x->where('year',  $req->year))
            ->when($req->filled('nivel'),  fn ($x) => $x->where('nivel', $req->nivel))
            ->when($req->filled('estado'), fn ($x) => $x->where('estado',$req->estado))
            ->orderBy('year', 'desc')
            ->orderBy('nivel')
            ->orderBy('curso')
            ->orderBy('paralelo');

        // Devolver paginado para listas grandes
        return $q->paginate(50);
    }

    // Validar capacidad según nivel (Ley 070 Bolivia)
    private function validateCapacidad($nivel, $capacidad)
    {
        if ($capacidad === null) return null;

        $max = self::CAPACIDAD_MAXIMA[$nivel] ?? 35;
        if ($capacidad > $max) {
            return "La capacidad máxima para nivel {$nivel} es {$max} estudiantes (Ley 070 Bolivia).";
        }
        return null;
    }

    // CREAR SECCIÓN
    public function store(Request $req)
    {
        $data = $req->validate([
            'year'         => ['required', 'digits:4'],
            'nivel'        => ['required', Rule::in(['inicial','primaria','secundaria'])],
            'curso'        => ['required', 'string'],
            'paralelo'     => ['required', 'string'],
            // Turnos con y sin tilde (Bolivia)
            'turno'        => ['required', Rule::in(['Mañana','Manana','Tarde','Noche'])],
            'aula'         => ['nullable', 'string'],
            'capacidad'    => ['nullable', 'integer', 'min:1'],
            'tutor_id'     => ['nullable', 'exists:teachers,id'],
            'tutor_nombre' => ['nullable', 'string', 'max:255'],
            'estado'       => ['required', Rule::in(['ACTIVA','CERRADA'])],
        ]);

        // Normalizar turno (guardar con tilde)
        if ($data['turno'] === 'Manana') {
            $data['turno'] = 'Mañana';
        }

        // Normalizar nivel a minúsculas
        $data['nivel'] = strtolower($data['nivel']);

        // Validar capacidad según Ley 070 Bolivia
        if (isset($data['capacidad'])) {
            $capacidadError = $this->validateCapacidad($data['nivel'], $data['capacidad']);
            if ($capacidadError) {
                return response()->json(['message' => $capacidadError], 422);
            }
        }

        // Unicidad (year + nivel + curso + paralelo)
        $exists = Section::where('year', $data['year'])
            ->where('nivel', $data['nivel'])
            ->where('curso', $data['curso'])
            ->where('paralelo', $data['paralelo'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'La sección ya existe (year + nivel + curso + paralelo).'
            ], 422);
        }

        $sec = Section::create($data);
        return response()->json($sec, 201);
    }

    // VER UNA SECCIÓN
    public function show(Section $section)
    {
        return $section;
    }

    // ACTUALIZAR SECCIÓN
    public function update(Request $req, Section $section)
    {
        $data = $req->validate([
            'year'         => ['sometimes', 'digits:4'],
            'nivel'        => ['sometimes', Rule::in(['inicial','primaria','secundaria'])],
            'curso'        => ['sometimes', 'string'],
            'paralelo'     => ['sometimes', 'string'],
            'turno'        => ['sometimes', Rule::in(['Mañana','Manana','Tarde','Noche'])],
            'aula'         => ['nullable', 'string'],
            'capacidad'    => ['nullable', 'integer', 'min:1'],
            'tutor_id'     => ['nullable', 'exists:teachers,id'],
            'tutor_nombre' => ['nullable', 'string', 'max:255'],
            'estado'       => ['sometimes', Rule::in(['ACTIVA','CERRADA'])],
        ]);

        // Normalizar turno (guardar con tilde)
        if (isset($data['turno']) && $data['turno'] === 'Manana') {
            $data['turno'] = 'Mañana';
        }

        // Normalizar nivel a minúsculas
        if (isset($data['nivel'])) {
            $data['nivel'] = strtolower($data['nivel']);
        }

        // Validar capacidad según Ley 070 Bolivia
        $nivel = $data['nivel'] ?? $section->nivel;
        if (isset($data['capacidad'])) {
            $capacidadError = $this->validateCapacidad($nivel, $data['capacidad']);
            if ($capacidadError) {
                return response()->json(['message' => $capacidadError], 422);
            }
        }

        // Si cambian las 4 llaves, validar unicidad contra otras secciones
        $year     = $data['year']     ?? $section->year;
        $nivel    = $data['nivel']    ?? $section->nivel;
        $curso    = $data['curso']    ?? $section->curso;
        $paralelo = $data['paralelo'] ?? $section->paralelo;

        $exists = Section::where('year', $year)
            ->where('nivel', $nivel)
            ->where('curso', $curso)
            ->where('paralelo', $paralelo)
            ->where('id', '<>', $section->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Duplicado (year + nivel + curso + paralelo).'
            ], 422);
        }

        $section->update($data);
        return $section;
    }

    // ELIMINAR SECCIÓN
    public function destroy(Section $section)
    {
        $section->delete();
        return response()->noContent();
    }

    // LISTAR CURSOS EXISTENTES (POR AÑO + NIVEL)
    public function cursos(Request $req)
    {
        $req->validate([
            'year'  => ['required', 'digits:4'],
            'nivel' => ['required', Rule::in(['inicial','primaria','secundaria'])],
        ]);

        $cursos = Section::query()
            ->where('year',  $req->query('year'))
            ->where('nivel', $req->query('nivel'))
            ->where('estado','ACTIVA')
            ->pluck('curso')
            ->unique()
            ->sort()
            ->values();

        return response()->json(['data' => $cursos]);
    }

    // LISTAR PARALELOS EXISTENTES (POR AÑO + NIVEL + CURSO)
    public function paralelos(Request $req)
    {
        $req->validate([
            'year'  => ['required', 'digits:4'],
            'nivel' => ['required', Rule::in(['inicial','primaria','secundaria'])],
            'curso' => ['required', 'string'],
        ]);

        $paralelos = Section::query()
            ->where('year',  $req->query('year'))
            ->where('nivel', $req->query('nivel'))
            ->where('curso', $req->query('curso'))
            ->where('estado','ACTIVA')
            ->pluck('paralelo')
            ->unique()
            ->sort()
            ->values();

        return response()->json(['data' => $paralelos]);
    }

    // VER ESTUDIANTES DE UNA SECCIÓN
    public function students(Section $section)
    {
        $estudiantes = $section->enrollments()
            ->with('student:id,codigo_estudiante,nombres,apellidos,ci,email,telefono')
            ->whereIn('estado', ['INSCRITO', 'PREINSCRITO'])
            ->get()
            ->map(function ($enrollment) {
                return [
                    'enrollment_id' => $enrollment->id,
                    'estado_matricula' => $enrollment->estado,
                    'fecha_inscripcion' => $enrollment->fecha,
                    ...(array) $enrollment->student->only(['id', 'codigo_estudiante', 'nombres', 'apellidos', 'ci', 'email', 'telefono']),
                ];
            });

        return response()->json([
            'section' => [
                'id' => $section->id,
                'curso' => $section->curso,
                'paralelo' => $section->paralelo,
                'capacidad' => $section->capacidad,
            ],
            'total' => $estudiantes->count(),
            'estudiantes' => $estudiantes,
        ]);
    }

    // DUPLICAR SECCIONES DEL AÑO ANTERIOR AL AÑO ACTUAL
    public function duplicateYear(Request $req)
    {
        $data = $req->validate([
            'source_year' => ['required', 'digits:4'],
            'target_year' => ['required', 'digits:4', 'different:source_year'],
            'nivel'       => ['nullable', Rule::in(['inicial', 'primaria', 'secundaria'])],
        ]);

        $sourceYear = $data['source_year'];
        $targetYear = $data['target_year'];

        // Buscar secciones del año origen
        $query = Section::where('year', $sourceYear)->where('estado', 'ACTIVA');
        if (!empty($data['nivel'])) {
            $query->where('nivel', $data['nivel']);
        }
        $sourceSections = $query->get();

        if ($sourceSections->isEmpty()) {
            return response()->json([
                'message' => "No hay secciones activas en el año {$sourceYear}."
            ], 404);
        }

        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($sourceSections as $source) {
            // Verificar si ya existe en el año destino
            $exists = Section::where('year', $targetYear)
                ->where('nivel', $source->nivel)
                ->where('curso', $source->curso)
                ->where('paralelo', $source->paralelo)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            try {
                Section::create([
                    'year'      => $targetYear,
                    'nivel'     => $source->nivel,
                    'curso'     => $source->curso,
                    'paralelo'  => $source->paralelo,
                    'turno'     => $source->turno,
                    'aula'      => $source->aula,
                    'capacidad' => $source->capacidad,
                    'tutor_id'  => null, // El tutor se asigna manualmente
                    'estado'    => 'ACTIVA',
                ]);
                $created++;
            } catch (\Exception $e) {
                $errors[] = "{$source->curso} {$source->paralelo}: {$e->getMessage()}";
            }
        }

        return response()->json([
            'message' => "Duplicación completada: {$created} creadas, {$skipped} omitidas (ya existían).",
            'created' => $created,
            'skipped' => $skipped,
            'errors'  => $errors,
        ]);
    }
}
