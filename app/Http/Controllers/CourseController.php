<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controlador para gestión de Cursos/Grados según Ley 070 Bolivia
 */
class CourseController extends Controller
{
    /**
     * Listar todos los cursos con filtros opcionales
     */
    public function index(Request $request)
    {
        $query = Course::query()
            ->when($request->nivel, fn($q) => $q->where('nivel', $request->nivel))
            ->when($request->activo !== null, fn($q) => $q->where('activo', $request->activo))
            ->when($request->search, function($q, $search) {
                $q->where(function($sq) use ($search) {
                    $sq->where('nombre', 'like', "%{$search}%")
                       ->orWhere('codigo', 'like', "%{$search}%");
                });
            });

        // Ordenar por nivel y grado
        $query->orderByRaw("FIELD(nivel, 'inicial', 'primaria', 'secundaria')")
              ->orderBy('grado');

        $courses = $query->get();

        // Agregar conteo de secciones manualmente
        foreach ($courses as $course) {
            $course->sections_count = \App\Models\Section::where('nivel', $course->nivel)
                ->where('curso', (string) $course->grado)
                ->count();
        }

        if ($request->has('per_page')) {
            return Course::query()
                ->when($request->nivel, fn($q) => $q->where('nivel', $request->nivel))
                ->orderByRaw("FIELD(nivel, 'inicial', 'primaria', 'secundaria')")
                ->orderBy('grado')
                ->paginate($request->per_page);
        }

        return response()->json([
            'data' => $courses,
            'niveles' => [
                ['value' => 'inicial', 'label' => 'Nivel Inicial', 'max_grados' => 2],
                ['value' => 'primaria', 'label' => 'Nivel Primario', 'max_grados' => 6],
                ['value' => 'secundaria', 'label' => 'Nivel Secundario', 'max_grados' => 6],
            ]
        ]);
    }

    /**
     * Crear nuevo curso
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'codigo' => ['required', 'string', 'max:10', 'unique:courses,codigo'],
            'nivel' => ['required', Rule::in(['inicial', 'primaria', 'secundaria'])],
            'grado' => ['required', 'integer', 'min:1', 'max:6'],
            'orden' => ['nullable', 'integer'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'activo' => ['nullable', 'boolean'],
        ]);

        // Validar grado máximo según nivel (Ley 070)
        $maxGrados = ['inicial' => 2, 'primaria' => 6, 'secundaria' => 6];
        if ($data['grado'] > $maxGrados[$data['nivel']]) {
            return response()->json([
                'message' => "El nivel {$data['nivel']} solo tiene {$maxGrados[$data['nivel']]} grados según Ley 070"
            ], 422);
        }

        // Verificar que no exista otro curso con mismo nivel y grado
        $existe = Course::where('nivel', $data['nivel'])
            ->where('grado', $data['grado'])
            ->exists();

        if ($existe) {
            return response()->json([
                'message' => "Ya existe un curso para {$data['grado']}° de {$data['nivel']}"
            ], 422);
        }

        // Calcular orden automático si no se proporciona
        if (!isset($data['orden'])) {
            $ordenBase = ['inicial' => 0, 'primaria' => 10, 'secundaria' => 20];
            $data['orden'] = $ordenBase[$data['nivel']] + $data['grado'];
        }

        $data['activo'] = $data['activo'] ?? true;

        $course = Course::create($data);

        return response()->json($course->loadCount('sections'), 201);
    }

    /**
     * Ver detalle de un curso
     */
    public function show(Course $course)
    {
        return response()->json(
            $course->loadCount('sections')->load('sections')
        );
    }

    /**
     * Actualizar curso
     */
    public function update(Request $request, Course $course)
    {
        $data = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:100'],
            'codigo' => ['sometimes', 'string', 'max:10', Rule::unique('courses')->ignore($course->id)],
            'nivel' => ['sometimes', Rule::in(['inicial', 'primaria', 'secundaria'])],
            'grado' => ['sometimes', 'integer', 'min:1', 'max:6'],
            'orden' => ['nullable', 'integer'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'activo' => ['nullable', 'boolean'],
        ]);

        // Validar grado máximo según nivel
        $nivel = $data['nivel'] ?? $course->nivel;
        $grado = $data['grado'] ?? $course->grado;
        $maxGrados = ['inicial' => 2, 'primaria' => 6, 'secundaria' => 6];

        if ($grado > $maxGrados[$nivel]) {
            return response()->json([
                'message' => "El nivel {$nivel} solo tiene {$maxGrados[$nivel]} grados según Ley 070"
            ], 422);
        }

        // Verificar duplicados si cambia nivel o grado
        if (isset($data['nivel']) || isset($data['grado'])) {
            $existe = Course::where('nivel', $nivel)
                ->where('grado', $grado)
                ->where('id', '!=', $course->id)
                ->exists();

            if ($existe) {
                return response()->json([
                    'message' => "Ya existe un curso para {$grado}° de {$nivel}"
                ], 422);
            }
        }

        $course->update($data);

        return response()->json($course->fresh()->loadCount('sections'));
    }

    /**
     * Eliminar curso
     */
    public function destroy(Course $course)
    {
        // Verificar que no tenga secciones activas
        if ($course->sections()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar el curso porque tiene secciones asociadas'
            ], 422);
        }

        $course->delete();

        return response()->json(['message' => 'Curso eliminado correctamente']);
    }

    /**
     * Generar cursos estándar de Bolivia (Ley 070)
     */
    public function generarEstandar(Request $request)
    {
        $cursosCreados = [];
        $niveles = [
            'inicial' => ['grados' => 2, 'prefijo' => 'I'],
            'primaria' => ['grados' => 6, 'prefijo' => 'P'],
            'secundaria' => ['grados' => 6, 'prefijo' => 'S'],
        ];

        $orden = 1;
        foreach ($niveles as $nivel => $config) {
            for ($grado = 1; $grado <= $config['grados']; $grado++) {
                $codigo = "{$grado}{$config['prefijo']}";

                // Solo crear si no existe
                if (!Course::where('codigo', $codigo)->exists()) {
                    $nombreNivel = ucfirst($nivel);
                    $sufijo = $grado == 1 ? 'er' : ($grado == 2 ? 'do' : ($grado == 3 ? 'er' : 'to'));
                    if ($grado > 3) $sufijo = 'to';
                    if ($grado == 4) $sufijo = 'to';
                    if ($grado == 5) $sufijo = 'to';
                    if ($grado == 6) $sufijo = 'to';

                    $course = Course::create([
                        'nombre' => "{$grado}° de {$nombreNivel}",
                        'codigo' => $codigo,
                        'nivel' => $nivel,
                        'grado' => $grado,
                        'orden' => $orden,
                        'descripcion' => "Curso {$grado}° del nivel {$nivel} - Ley 070",
                        'activo' => true,
                    ]);

                    $cursosCreados[] = $course;
                }
                $orden++;
            }
        }

        return response()->json([
            'message' => count($cursosCreados) . ' cursos creados correctamente',
            'data' => $cursosCreados
        ], 201);
    }
}
