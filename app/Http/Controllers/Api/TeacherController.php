<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Assignment;
use App\Models\Section;
use App\Models\Level;
use App\Services\CredencialesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;


/**
 * @OA\Schema(
 *     schema="Teacher",
 *     type="object",
 *     required={"nombres","apellidos","email","ci","rda_numero","rda_estado","estado","anio_ingreso","mes_ingreso"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombres", type="string", maxLength=255, example="Carlos"),
 *     @OA\Property(property="apellidos", type="string", maxLength=255, example="Pérez Sánchez"),
 *     @OA\Property(property="email", type="string", format="email", example="cperez@colegio.com"),
 *     @OA\Property(property="telefono", type="string", maxLength=50, nullable=true, example="+591 71234567"),
 *     @OA\Property(property="ci", type="string", maxLength=50, example="1234567 LP"),
 *     @OA\Property(property="rda_numero", type="string", maxLength=100, example="RDA-2024-001"),
 *     @OA\Property(property="rda_estado", type="string", enum={"VIGENTE","OBSERVADO","VENCIDO"}, example="VIGENTE"),
 *     @OA\Property(property="estado", type="string", enum={"activo","licencia","inactivo"}, example="activo"),
 *     @OA\Property(property="anio_ingreso", type="string", maxLength=4, example="2024"),
 *     @OA\Property(property="mes_ingreso", type="string", maxLength=2, example="01"),
 *     @OA\Property(property="usuario", type="string", example="cperez"),
 *     @OA\Property(property="password_temp", type="string", example="DOC202401001"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class TeacherController extends Controller
{
    protected CredencialesService $credenciales;

    public function __construct(CredencialesService $credenciales)
    {
        $this->credenciales = $credenciales;
    }

    /**
     * @OA\Get(
     *     path="/api/teachers",
     *     summary="Listar docentes",
     *     description="Obtiene un listado paginado de docentes con filtros opcionales por estado activo y búsqueda.",
     *     operationId="getTeachers",
     *     tags={"Docentes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="activo", in="query", description="Filtrar por estado activo", required=false, @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="q", in="query", description="Búsqueda por nombres, apellidos o RDA", required=false, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de docentes paginada",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Teacher")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="per_page", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function index(Request $request)
    {
        $q = Teacher::query()
            ->with('levels')
            ->when($request->activo !== null, fn($x) =>
                $x->where('activo', filter_var($request->activo, FILTER_VALIDATE_BOOL))
            )
            ->when($request->estado, fn($x) =>
                $x->where('estado', $request->estado)
            )
            ->when($request->rda_estado, fn($x) =>
                $x->where('rda_estado', $request->rda_estado)
            )
            ->when($request->q, fn($x) => $x->where(function ($y) use ($request) {
                $term = $request->q;
                $y->where('nombres', 'like', "%$term%")
                  ->orWhere('apellidos', 'like', "%$term%")
                  ->orWhere('rda_numero', 'like', "%$term%");
            }))
            ->orderBy('apellidos')
            ->orderBy('nombres');

        return response()->json($q->paginate(50));
    }

    /**
     * @OA\Post(
     *     path="/api/teachers",
     *     summary="Crear nuevo docente",
     *     description="Crea un nuevo docente y su usuario asociado. Genera automáticamente usuario y contraseña temporal.",
     *     operationId="createTeacher",
     *     tags={"Docentes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombres","apellidos","email","ci","rda_numero","rda_estado","estado","anio_ingreso","mes_ingreso"},
     *             @OA\Property(property="nombres", type="string", example="Carlos"),
     *             @OA\Property(property="apellidos", type="string", example="Pérez Sánchez"),
     *             @OA\Property(property="email", type="string", format="email", example="cperez@colegio.com"),
     *             @OA\Property(property="telefono", type="string", example="+591 71234567"),
     *             @OA\Property(property="ci", type="string", example="1234567 LP"),
     *             @OA\Property(property="rda_numero", type="string", example="RDA-2024-001"),
     *             @OA\Property(property="rda_estado", type="string", enum={"VIGENTE","OBSERVADO","VENCIDO"}, example="VIGENTE"),
     *             @OA\Property(property="estado", type="string", enum={"activo","licencia","inactivo"}, example="activo"),
     *             @OA\Property(property="anio_ingreso", type="string", example="2024"),
     *             @OA\Property(property="mes_ingreso", type="string", example="01")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Docente creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="teacher", ref="#/components/schemas/Teacher"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="username", type="string"),
     *                 @OA\Property(property="role", type="string", example="teacher")
     *             ),
     *             @OA\Property(property="password_temp", type="string", example="DOC202401001")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado")
     * )
     */
    public function store(Request $request)
    {
        // Log incoming request for debugging (validation errors will also be logged)
        Log::info('Create teacher request received', ['input' => $request->all(), 'user' => $request->user()?->id]);

        try {
            $data = $request->validate([
                'nombres'      => 'required|string|max:255',
                'apellidos'    => 'required|string|max:255',
                'email'        => 'required|email|unique:teachers,email',
                'telefono'     => 'nullable|string|max:50',
                'ci'           => 'required|string|max:50|unique:teachers,ci',
                'rda_numero'   => 'required|string|max:100|unique:teachers,rda_numero',
                'rda_estado'   => ['required', Rule::in(['VIGENTE','OBSERVADO','VENCIDO'])],
                'estado'       => ['required', Rule::in(['activo','licencia','inactivo'])],
                'anio_ingreso' => 'required|string|size:4',
                'mes_ingreso'  => 'required|string|size:2',
                'levels'       => 'nullable|array',
                'levels.*'     => 'integer|exists:levels,id',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Teacher validation failed', [
                'errors' => $e->errors(),
                'input' => $request->all(),
            ]);
            throw $e;
        }

        $nextId = (Teacher::max('id') ?? 0) + 1;
        $existingUsers = User::pluck('username')->all();
        $username = $this->credenciales->buildUsername(
            $data['apellidos'],
            $data['nombres'],
            $existingUsers,
            $data['email']
        );
        $passwordTemp = $this->credenciales->makeTeacherPassword(
            $data['anio_ingreso'],
            $data['mes_ingreso'],
            $nextId
        );

        $teacher = Teacher::create([
            ...$data,
            'usuario'       => $username,
            'password_temp' => $passwordTemp,
        ]);

        // Sincronizar niveles si vienen en la petición
        if (!empty($data['levels']) && is_array($data['levels'])) {
            $teacher->levels()->sync($data['levels']);
        }

        Log::info('Teacher created (db)', ['teacher_id' => $teacher->id, 'usuario' => $username]);

        $user = User::create([
            'name'                 => $data['nombres'] . ' ' . $data['apellidos'], // 👈
            'username'             => $username,
            'email'                => $data['email'],
            'password'             => Hash::make($passwordTemp),
            'role'                 => 'teacher',
            'must_change_password' => true,
            'teacher_id'           => $teacher->id,
        ]);

        Log::info('User created for teacher', ['user_id' => $user->id, 'username' => $user->username]);

        $teacher->load('levels');
        return response()->json([
            'teacher' => $teacher,
            'user' => $user,
            'password_temp' => $passwordTemp,
        ], 201);
    }


    /**
     * @OA\Get(
     *     path="/api/teachers/{id}",
     *     summary="Ver detalle de docente",
     *     description="Obtiene los detalles completos de un docente específico.",
     *     operationId="getTeacher",
     *     tags={"Docentes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del docente", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalle del docente", @OA\JsonContent(ref="#/components/schemas/Teacher")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Docente no encontrado")
     * )
     */
    public function show(Teacher $teacher)
    {
        return response()->json($teacher);
    }

    /**
     * @OA\Put(
     *     path="/api/teachers/{id}",
     *     summary="Actualizar docente",
     *     description="Actualiza los datos de un docente existente.",
     *     operationId="updateTeacher",
     *     tags={"Docentes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del docente", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="nombres", type="string"),
     *             @OA\Property(property="apellidos", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="telefono", type="string"),
     *             @OA\Property(property="ci", type="string"),
     *             @OA\Property(property="rda_numero", type="string"),
     *             @OA\Property(property="rda_estado", type="string", enum={"VIGENTE","OBSERVADO","VENCIDO"}),
     *             @OA\Property(property="estado", type="string", enum={"activo","licencia","inactivo"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Docente actualizado", @OA\JsonContent(ref="#/components/schemas/Teacher")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Docente no encontrado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function update(Request $request, Teacher $teacher)
    {
        $data = $request->validate([
            'nombres'      => 'sometimes|string|max:255',
            'apellidos'    => 'sometimes|string|max:255',
            'email'        => 'sometimes|email|unique:teachers,email,' . $teacher->id,
            'telefono'     => 'nullable|string|max:50',
            'ci'           => 'sometimes|string|max:50|unique:teachers,ci,' . $teacher->id,
            'rda_numero'   => 'sometimes|string|max:100|unique:teachers,rda_numero,' . $teacher->id,
            'rda_estado'   => ['sometimes', Rule::in(['VIGENTE','OBSERVADO','VENCIDO'])],
            'estado'       => ['sometimes', Rule::in(['activo','licencia','inactivo'])],
            'levels'       => 'nullable|array',
            'levels.*'     => 'integer|exists:levels,id',
        ]);

        $teacher->update($data);
        if (array_key_exists('levels', $data)) {
            $teacher->levels()->sync($data['levels'] ?? []);
        }
        return response()->json($teacher->load('levels'));
    }

    /**
     * @OA\Delete(
     *     path="/api/teachers/{id}",
     *     summary="Eliminar docente",
     *     description="Elimina un docente del sistema.",
     *     operationId="deleteTeacher",
     *     tags={"Docentes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del docente", @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Docente eliminado exitosamente"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Docente no encontrado")
     * )
     */
    public function destroy(Teacher $teacher)
    {
        $teacher->delete();
        return response()->noContent();
    }

    /**
     * Devuelve datos del docente autenticado (para panel docente)
     */
public function me(Request $request)
{
    $user = $request->user();
    if (!$user || !$user->teacher_id) {
        return response()->json(['message' => 'No teacher profile available for this user'], 404);
    }

    $year = (int) $request->query('year', date('Y'));

    $teacher = Teacher::query()
        ->with('levels')
        ->withCount(['assignments as assignments_count' => function ($q) use ($year) {
            $q->where('year', $year)->where('estado', 'ACTIVO');
        }])
        ->find($user->teacher_id);

    if (!$teacher) {
        return response()->json(['message' => 'Teacher not found'], 404);
    }

    return response()->json([
        'id' => $teacher->id,
        'nombres' => $teacher->nombres,
        'apellidos' => $teacher->apellidos,
        'email' => $teacher->email,
        'ci' => $teacher->ci,
        'telefono' => $teacher->telefono,
        'rda_numero' => $teacher->rda_numero,
        'rda_estado' => $teacher->rda_estado,
        'rda_vencimiento' => $teacher->rda_vencimiento,
        'estado' => $teacher->estado,
        'especialidad' => $teacher->especialidad,
        'nivel_formacion' => $teacher->nivel_formacion,
        'turno' => $teacher->turno,
        'fecha_ingreso' => $teacher->fecha_ingreso,
        'anio_ingreso' => $teacher->anio_ingreso,
        'mes_ingreso' => $teacher->mes_ingreso,
        'usuario' => $teacher->usuario,
        'levels' => $teacher->levels,
        'assignments_count' => $teacher->assignments_count,
        'selected_level_slug' => $request->query('nivel'),
        'allowed_levels' => $teacher->levels->pluck('slug'),
    ]);
}

    /**
     * Permite que el docente seleccione (o actualice) sus niveles.
     * Método accesible para cualquier docente autenticado.
     */
    public function setLevels(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->teacher_id) {
            return response()->json(['message' => 'No teacher profile available for this user'], 404);
        }

        $data = $request->validate([
            'levels' => 'required|array',
            'levels.*' => 'integer|exists:levels,id'
        ]);

        $teacher = Teacher::find($user->teacher_id);
        if (!$teacher) return response()->json(['message' => 'Teacher not found'], 404);

        $teacher->levels()->sync($data['levels']);
        return response()->json($teacher->load('levels'));
    }

    /**
     * @OA\Get(
     *     path="/api/teachers/levels",
     *     summary="Obtener niveles recomendados para el docente",
     *     description="Devuelve los niveles disponibles y recomendados para el docente autenticado, basados en sus asignaciones.",
     *     operationId="getTeacherLevels",
     *     tags={"Docentes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Niveles recomendados obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="teacher_id", type="integer"),
     *             @OA\Property(property="recommended_levels", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="slug", type="string"),
     *                     @OA\Property(property="assignments_count", type="integer"),
     *                     @OA\Property(property="allowed", type="boolean")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Docente no encontrado")
     * )
     */
    public function levels(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->teacher_id) {
            return response()->json(['message' => 'No teacher profile available for this user'], 404);
        }

        $year = (int) $request->query('year', date('Y'));

        $niveles = Assignment::query()
            ->where('teacher_id', $user->teacher_id)
            ->where('year', $year)
            ->where('estado', 'ACTIVO')
            ->with('section:id,nivel')
            ->get()
            ->pluck('section.nivel')
            ->filter()
            ->map(fn($x) => strtolower($x))
            ->unique()
            ->values()
            ->all();

        if (empty($niveles)) {
            $teacher = Teacher::with('levels:id,name,slug')->find($user->teacher_id);

            return response()->json([
                'year' => $year,
                'recommended_levels' => $teacher?->levels ?? [],
                'recommended_slugs' => ($teacher?->levels?->pluck('slug') ?? collect())->values(),
                'source' => 'pivot_levels',
            ]);
        }

        $recommended = Level::query()
            ->whereIn('slug', $niveles)
            ->orderBy('id')
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'year' => $year,
            'recommended_levels' => $recommended,
            'recommended_slugs' => $niveles,
            'source' => 'assignments',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/teachers/sections",
     *     summary="Obtener secciones del docente",
     *     description="Devuelve las secciones asignadas al docente autenticado en un año y nivel determinados.",
     *     operationId="getTeacherSections",
     *     tags={"Docentes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="year", in="query", description="Año escolar", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="nivel", in="query", description="Nivel educativo (por ejemplo, 'primaria')", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Cantidad de resultados por página", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Secciones obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="nombre", type="string"),
     *                     @OA\Property(property="nivel", type="string"),
     *                     @OA\Property(property="paralelo", type="string"),
     *                     @OA\Property(property="turno", type="string"),
     *                     @OA\Property(property="anio", type="integer"),
     *                     @OA\Property(property="estado", type="string"),
     *                     @OA\Property(property="assignments", type="array",
     *                         @OA\Items(type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="subject_id", type="integer"),
     *                             @OA\Property(property="teacher_id", type="integer"),
     *                             @OA\Property(property="year", type="integer"),
     *                             @OA\Property(property="estado", type="string"),
     *                             @OA\Property(property="subject", type="object",
     *                                 @OA\Property(property="id", type="integer"),
     *                                 @OA\Property(property="nombre", type="string"),
     *                                 @OA\Property(property="nivel", type="string"),
     *                                 @OA\Property(property="area", type="string")
     *                             )
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="per_page", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Docente no encontrado")
     * )
     */
    public function sections(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->teacher_id) {
            return response()->json(['message' => 'No teacher profile available for this user'], 404);
        }

        $year = (int) $request->query('year', date('Y'));
        $nivel = $request->query('nivel');
        $nivel = $nivel ? strtolower($nivel) : null;

        $perPage = (int) $request->query('per_page', 50);
        $perPage = max(1, min($perPage, 200));

        $q = Section::query()
            ->where('year', $year)
            ->when($nivel, fn ($x) => $x->where('nivel', $nivel))
            ->whereHas('assignments', function ($a) use ($user, $year) {
                $a->where('teacher_id', $user->teacher_id)
                  ->where('year', $year)
                  ->where('estado', 'ACTIVO');
            })
            ->with(['assignments' => function ($a) use ($user, $year) {
                $a->where('teacher_id', $user->teacher_id)
                  ->where('year', $year)
                  ->where('estado', 'ACTIVO')
                  ->with('subject:id,nombre,nivel,area');
            }])
            ->orderBy('curso')
            ->orderBy('paralelo');

        return response()->json($q->paginate($perPage));
    }

    /** Alumnos inscritos en una sección asignada al docente autenticado. */
    public function sectionStudents(Request $request, Section $section)
    {
        $user = $request->user();
        if (!$user || !$user->teacher_id) {
            return response()->json(['message' => 'No teacher profile available for this user'], 404);
        }

        $assigned = Assignment::query()
            ->where('section_id', $section->id)
            ->where('teacher_id', $user->teacher_id)
            ->where('year', $section->year)
            ->where('estado', 'ACTIVO')
            ->exists();

        if (!$assigned) {
            return response()->json(['message' => 'No tienes acceso a los estudiantes de esta sección.'], 403);
        }

        $students = $section->enrollments()
            ->with('student:id,nombres,apellidos,ci,email,telefono')
            ->whereIn('estado', ['INSCRITO', 'PREINSCRITO'])
            ->get()
            ->filter(fn ($enrollment) => $enrollment->student)
            ->map(fn ($enrollment) => [
                'enrollment_id' => $enrollment->id,
                'estado_matricula' => $enrollment->estado,
                'fecha_inscripcion' => $enrollment->fecha,
                ...$enrollment->student->only(['id', 'nombres', 'apellidos', 'ci', 'email', 'telefono']),
            ])
            ->values();

        return response()->json([
            'section' => [
                'id' => $section->id,
                'curso' => $section->curso,
                'paralelo' => $section->paralelo,
                'capacidad' => $section->capacidad,
            ],
            'total' => $students->count(),
            'estudiantes' => $students,
        ]);
    }
}

