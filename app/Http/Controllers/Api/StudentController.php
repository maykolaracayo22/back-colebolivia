<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Models\Enrollment;
use App\Services\CredencialesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;


/**
 * @OA\Schema(
 *     schema="Student",
 *     type="object",
 *     required={"nombres","apellidos","nivel","curso","paralelo","anio_ingreso","mes_ingreso"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="codigo", type="string", example="EST-2024-001"),
 *     @OA\Property(property="nombres", type="string", maxLength=255, example="María"),
 *     @OA\Property(property="apellidos", type="string", maxLength=255, example="García López"),
 *     @OA\Property(property="nivel", type="string", enum={"inicial","primaria","secundaria"}, example="secundaria"),
 *     @OA\Property(property="curso", type="string", maxLength=255, example="6to de Secundaria"),
 *     @OA\Property(property="paralelo", type="string", maxLength=10, example="A"),
 *     @OA\Property(property="email", type="string", format="email", nullable=true, example="mgarcia@colegio.com"),
 *     @OA\Property(property="fecha_nac", type="string", format="date", nullable=true, example="2010-05-15"),
 *     @OA\Property(property="sexo", type="string", enum={"M","F"}, nullable=true, example="F"),
 *     @OA\Property(property="anio_ingreso", type="string", maxLength=4, example="2024"),
 *     @OA\Property(property="mes_ingreso", type="string", maxLength=2, example="02"),
 *     @OA\Property(property="usuario", type="string", example="mgarcia"),
 *     @OA\Property(property="password_temp", type="string", example="EST202402001"),
 *     @OA\Property(property="activo", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class StudentController extends Controller
{
    protected CredencialesService $credenciales;

    public function __construct(CredencialesService $credenciales)
    {
        $this->credenciales = $credenciales;
    }

    /** Contexto del estudiante autenticado para cargar contenidos de su sección. */
    public function myContext(Request $request)
    {
        $student = $request->user()->student;
        if (!$student) {
            return response()->json(['message' => 'La cuenta no está vinculada a un estudiante.'], 404);
        }

        $year = (string) now()->year;
        $enrollment = Enrollment::query()
            ->with('section')
            ->where('student_id', $student->id)
            ->where('year', $year)
            ->whereIn('estado', ['PREINSCRITO', 'INSCRITO'])
            ->latest('id')
            ->first();

        return response()->json([
            'student_id' => $student->id,
            'section_id' => $enrollment?->section_id,
            'year' => $year,
            'student' => $student->only([
                'id', 'codigo', 'nombres', 'apellidos', 'nivel', 'curso', 'paralelo',
                'email', 'telefono', 'fecha_nac', 'sexo', 'anio_ingreso', 'mes_ingreso',
                'rude', 'ci', 'direccion', 'activo',
            ]),
            'section' => $enrollment?->section?->only(['id', 'nivel', 'curso', 'paralelo', 'turno']),
        ]);
    }
    /**
     * @OA\Get(
     *     path="/api/students",
     *     summary="Listar estudiantes",
     *     description="Obtiene un listado paginado de estudiantes con filtros por nivel, curso, paralelo, activo y búsqueda.",
     *     operationId="getStudents",
     *     tags={"Estudiantes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="nivel", in="query", description="Filtrar por nivel (inicial/primaria/secundaria)", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="curso", in="query", description="Filtrar por curso/grado", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="paralelo", in="query", description="Filtrar por paralelo", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="activo", in="query", description="Filtrar por activo (true/false)", required=false, @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="q", in="query", description="Búsqueda por nombres, apellidos o código", required=false, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de estudiantes paginada",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Student")),
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
        $q = Student::query()
            ->when($request->nivel, fn($x) => $x->where('nivel', $request->nivel))
            ->when($request->curso, fn($x) => $x->where('curso', $request->curso))
            ->when($request->paralelo, fn($x) => $x->where('paralelo', $request->paralelo))
            ->when($request->activo !== null, fn($x) => $x->where('activo', filter_var($request->activo, FILTER_VALIDATE_BOOL)))
            ->when($request->q, fn($x) => $x->where(function($y) use ($request) {
                $q = $request->q;
                $y->where('nombres', 'like', "%$q%")
                  ->orWhere('apellidos', 'like', "%$q%")
                  ->orWhere('codigo', 'like', "%$q%");
            }))
            ->orderBy('apellidos')->orderBy('nombres');

        return response()->json($q->paginate(50));
    }
    /**
     * @OA\Post(
     *     path="/api/students",
     *     summary="Crear nuevo estudiante",
     *     description="Crea un nuevo estudiante y su usuario asociado. Genera automáticamente código, usuario y contraseña temporal.",
     *     operationId="createStudent",
     *     tags={"Estudiantes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombres","apellidos","nivel","curso","paralelo","anio_ingreso","mes_ingreso"},
     *             @OA\Property(property="nombres", type="string", example="María"),
     *             @OA\Property(property="apellidos", type="string", example="García López"),
     *             @OA\Property(property="nivel", type="string", enum={"inicial","primaria","secundaria"}, example="secundaria"),
     *             @OA\Property(property="curso", type="string", example="6to de Secundaria"),
     *             @OA\Property(property="paralelo", type="string", example="A"),
     *             @OA\Property(property="email", type="string", format="email", example="mgarcia@colegio.com"),
     *             @OA\Property(property="anio_ingreso", type="string", example="2024"),
     *             @OA\Property(property="mes_ingreso", type="string", example="02"),
     *             @OA\Property(property="fecha_nac", type="string", format="date", example="2010-05-15"),
     *             @OA\Property(property="sexo", type="string", enum={"M","F"}, example="F")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Estudiante creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="student", ref="#/components/schemas/Student"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="username", type="string"),
     *                 @OA\Property(property="role", type="string", example="student")
     *             ),
     *             @OA\Property(property="password_temp", type="string", example="EST202402001")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombres'      => 'required|string|max:255',
            'apellidos'    => 'required|string|max:255',
            'nivel'        => ['required', Rule::in(['inicial','primaria','secundaria'])],
            'curso'        => 'required|string|max:255',
            'paralelo'     => 'required|string|max:10',
            'email'        => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('students', 'email'),
            ],
            'anio_ingreso' => 'required|string|size:4',
            'mes_ingreso'  => 'required|string|size:2',
            'fecha_nac'    => 'nullable|date',
            'sexo'         => ['nullable', Rule::in(['M','F'])],
        ]);

        $nextId = (Student::max('id') ?? 0) + 1;
        $codigo = sprintf("EST-%s-%03d", $data['anio_ingreso'], $nextId);
        $existingUsers = User::pluck('username')->all();
        $usernameBase = $this->credenciales->buildUsername(
            $data['apellidos'],
            $data['nombres'],
            $existingUsers
        );

        $username = $usernameBase;
        $contador = 2;

        // Mientras exista en users.username O en students.usuario, le agregamos un número
        while (
            in_array($username, $existingUsers, true) ||
            Student::where('usuario', $username)->exists()
        ) {
            $username = $usernameBase . $contador;
            $contador++;
        }

        $passwordTemp = $this->credenciales->makeStudentPassword(
            $data['anio_ingreso'],
            $data['mes_ingreso'],
            $nextId
        );


        [$student, $user] = DB::transaction(function () use ($data, $codigo, $username, $passwordTemp) {
            $student = Student::create([
                ...$data,
                'codigo'        => $codigo,
                'usuario'       => $username,
                'password_temp' => $passwordTemp,
                'activo'        => true,
            ]);

            $user = User::create([
                'name'                 => $data['nombres'] . ' ' . $data['apellidos'],
                'username'             => $username,
                'email'                => $data['email'] ?? null,
                'password'             => Hash::make($passwordTemp),
                'role'                 => 'student',
                'must_change_password' => true,
                'student_id'           => $student->id,
            ]);

            return [$student, $user];
        });

        return response()->json([
            'student'      => $student,
            'user'         => $user->only(['id','username','role']),
            'password_temp'=> $passwordTemp,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/students/{id}",
     *     summary="Ver detalle de estudiante",
     *     description="Obtiene los detalles completos de un estudiante específico.",
     *     operationId="getStudent",
     *     tags={"Estudiantes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del estudiante", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalle del estudiante", @OA\JsonContent(ref="#/components/schemas/Student")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Estudiante no encontrado")
     * )
     */
    public function show(Student $student)
    {
        return response()->json($student);
    }

    /**
     * @OA\Put(
     *     path="/api/students/{id}",
     *     summary="Actualizar estudiante",
     *     description="Actualiza los datos de un estudiante existente.",
     *     operationId="updateStudent",
     *     tags={"Estudiantes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del estudiante", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="nombres", type="string"),
     *             @OA\Property(property="apellidos", type="string"),
     *             @OA\Property(property="nivel", type="string", enum={"inicial","primaria","secundaria"}),
     *             @OA\Property(property="curso", type="string"),
     *             @OA\Property(property="paralelo", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="fecha_nac", type="string", format="date"),
     *             @OA\Property(property="sexo", type="string", enum={"M","F"}),
     *             @OA\Property(property="activo", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Estudiante actualizado", @OA\JsonContent(ref="#/components/schemas/Student")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Estudiante no encontrado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'nombres'      => 'sometimes|string|max:255',
            'apellidos'    => 'sometimes|string|max:255',
            'nivel'        => ['sometimes', Rule::in(['inicial','primaria','secundaria'])],
            'curso'        => 'sometimes|string|max:255',
            'paralelo'     => 'sometimes|string|max:10',
            'email'        => 'sometimes|email',
            'fecha_nac'    => 'nullable|date',
            'sexo'         => ['nullable', Rule::in(['M','F'])],
            'activo'       => 'sometimes|boolean',
        ]);

        $student->update($data);
        return response()->json($student);
    }

    /**
     * @OA\Delete(
     *     path="/api/students/{id}",
     *     summary="Eliminar estudiante",
     *     description="Elimina un estudiante del sistema.",
     *     operationId="deleteStudent",
     *     tags={"Estudiantes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del estudiante", @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Estudiante eliminado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Estudiante no encontrado")
     * )
     */
    public function destroy(Student $student)
    {
        $student->delete();
        return response()->noContent();
    }
}
