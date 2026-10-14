<?php

namespace App\Http\Controllers;

use App\Models\Preinscription;
use App\Models\Student;
use App\Models\ParentModel;
use App\Models\User;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenApi\Annotations as OA;

class PreinscriptionController extends Controller
{
        public function index(Request $request)
    {
        try {
            $query = Preinscription::query();

            // Filtrar por año usando created_at
            if ($request->filled('year')) {
                $query->whereYear('created_at', $request->input('year'));
            }

            // Filtrar por estado: pendiente / aprobada / rechazada
            if ($request->filled('estado')) {
                $query->where('estado', $request->input('estado'));
            }

            // Filtrar por nivel: inicial / primaria / secundaria
            if ($request->filled('nivel')) {
                $query->where('nivel', $request->input('nivel'));
            }

            // Filtrar por grado
            if ($request->filled('grado')) {
                $query->where('grado', $request->input('grado'));
            }

            // Búsqueda por nombre/apellidos del estudiante
            if ($request->filled('q')) {
                $q = $request->input('q');
                $query->where(function ($sub) use ($q) {
                    $sub->where('nombres', 'like', "%{$q}%")
                        ->orWhere('apellidos', 'like', "%{$q}%")
                        ->orWhere('tutor_nombre', 'like', "%{$q}%");
                });
            }

            // Ordenar de la más reciente a la más antigua
            $preinscripciones = $query
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json($preinscripciones);
        } catch (\Exception $e) {
            Log::error('Error fetching preinscriptions', [
                'exception' => $e,
                'filters' => $request->all(),
            ]);
            return response()->json(['message' => 'Error al listar preinscripciones: ' . $e->getMessage()], 500);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/preinscriptions",
     *     summary="Crear preinscripción (público)",
     *     description="Registra una preinscripción con datos del estudiante y tutor. Permite adjuntar comprobante.",
     *     operationId="createPreinscription",
     *     tags={"Preinscriptions"},
     *     security={},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"nivel","grado","nombres","apellidos","sexo","fecha_nac","tutor_nombre","tutor_parentesco"},
     *                 @OA\Property(property="nivel", type="string", enum={"inicial","primaria","secundaria"}),
     *                 @OA\Property(property="grado", type="integer"),
     *                 @OA\Property(property="nombres", type="string"),
     *                 @OA\Property(property="apellidos", type="string"),
     *                 @OA\Property(property="sexo", type="string", enum={"M","F"}),
     *                 @OA\Property(property="fecha_nac", type="string", format="date"),
     *                 @OA\Property(property="ci", type="string"),
     *                 @OA\Property(property="nacionalidad", type="string"),
     *                 @OA\Property(property="dep_nac", type="string"),
     *                 @OA\Property(property="muni_nac", type="string"),
     *                 @OA\Property(property="direccion", type="string"),
     *                 @OA\Property(property="telefono", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="pueblo_indigena", type="string"),
     *                 @OA\Property(property="discapacidad", type="boolean"),
     *                 @OA\Property(property="idiomas", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="vulnerabilidad", type="boolean"),
     *                 @OA\Property(property="tutor_nombre", type="string"),
     *                 @OA\Property(property="tutor_ci", type="string"),
     *                 @OA\Property(property="tutor_parentesco", type="string"),
     *                 @OA\Property(property="tutor_telefono", type="string"),
     *                 @OA\Property(property="comprobante", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Preinscripción creada", @OA\JsonContent(ref="#/components/schemas/Preinscription")),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nivel' => 'required|in:inicial,primaria,secundaria',
            'grado' => 'required|integer|min:1|max:12',
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'sexo' => 'required|in:M,F',
            'fecha_nac' => 'required|date',
            'ci' => 'nullable|string|max:20',
            'nacionalidad' => 'nullable|string|max:100',
            'dep_nac' => 'nullable|string|max:100',
            'muni_nac' => 'nullable|string|max:100',
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'pueblo_indigena' => 'nullable|string|max:100',
            'discapacidad' => 'boolean',
            'idiomas' => 'nullable|array',
            'vulnerabilidad' => 'boolean',
            'tutor_nombre' => 'required|string|max:255',
            'tutor_ci' => 'nullable|string|max:20',
            'tutor_parentesco' => 'required|string|max:100',
            'tutor_telefono' => 'nullable|string|max:20',
            'comprobante' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048', // Archivo de comprobante
        ]);

        // Generar cÃ³digo Ãºnico de preinscripciÃ³n
        $codigo = 'PRE-' . strtoupper(Str::random(8));

        // Subir comprobante si existe
        $comprobantePath = null;
        if ($request->hasFile('comprobante')) {
            $comprobantePath = $request->file('comprobante')->store('preinscriptions', 'public');
        }

        $preinscription = Preinscription::create(array_merge($data, [
            'codigo_preinscripcion' => $codigo,
            'comprobante_path' => $comprobantePath,
        ]));

        return response()->json($preinscription, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/preinscriptions/{codigo}",
     *     summary="Ver preinscripción por código (público)",
     *     description="Obtiene los datos de una preinscripción mediante su código único.",
     *     operationId="getPreinscriptionByCode",
     *     tags={"Preinscriptions"},
     *     security={},
     *     @OA\Parameter(name="codigo", in="path", required=true, description="Código de preinscripción", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Detalle", @OA\JsonContent(ref="#/components/schemas/Preinscription")),
     *     @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function show($codigo)
    {
        $preinscription = Preinscription::where('codigo_preinscripcion', $codigo)->firstOrFail();
        return response()->json($preinscription);
    }

    /**
     * @OA\Patch(
     *     path="/api/preinscriptions/{id}/approve",
     *     summary="Aprobar preinscripción",
     *     description="Aprueba una preinscripción y registra al aprobador.",
     *     operationId="approvePreinscription",
     *     tags={"Preinscriptions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de la preinscripción", @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=false, @OA\JsonContent(@OA\Property(property="observaciones", type="string"))),
     *     @OA\Response(response=200, description="Aprobado", @OA\JsonContent(ref="#/components/schemas/Preinscription")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function approve(Request $request, $id)
    {
        $this->authorize('approve', Preinscription::class);

        $preinscription = Preinscription::findOrFail($id);

        Log::info('Aprobando preinscripción', [
            'id' => $id,
            'path' => $request->path(),
            'method' => $request->method(),
            'inputs' => $request->all(),
            'user' => $request->user() ? $request->user()->id : null,
        ]);

        // Verificar que no esté ya aprobada
        if ($preinscription->estado === 'aprobado') {
            return response()->json([
                'message' => 'Esta preinscripción ya fue aprobada'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Generar código de estudiante
            $year = date('Y');
            $nivelCode = strtoupper(substr($preinscription->nivel, 0, 3)); // INI, PRI, SEC
            $lastStudent = Student::where('codigo', 'like', "{$year}{$nivelCode}%")
                ->orderBy('codigo', 'desc')
                ->first();
            $sequence = $lastStudent
                ? (int) substr($lastStudent->codigo, -4) + 1
                : 1;
            $codigoEstudiante = $year . $nivelCode . str_pad($sequence, 4, '0', STR_PAD_LEFT);

            // 2. Generar usuario y contraseña temporal para estudiante
            $usuario = strtolower($preinscription->apellidos) . '.' . strtolower($preinscription->nombres[0]) . rand(10, 99);
            $usuario = Str::slug($usuario, '');
            $passwordTemp = Str::random(8);

        // Asegurar que el nombre de usuario sea único en la tabla users
        $baseUsuario = $usuario;
        $uniqueUsuario = $baseUsuario;
        $uCounter = 1;
        while (User::where('username', $uniqueUsuario)->exists()) {
            $uniqueUsuario = $baseUsuario . $uCounter++;
        }

        // 3. Crear usuario del sistema para el estudiante
        $userEstudiante = User::create([
            'name' => $preinscription->nombres . ' ' . $preinscription->apellidos,
            'email' => $preinscription->email ?: $codigoEstudiante . '@colegio.edu.bo',
            'username' => $uniqueUsuario,
            'password' => Hash::make($passwordTemp),
        ]);
        // Asignar rol de estudiante; buscar rol con guard 'sanctum' y si no existe usar cualquier guard
        $studentRole = \Spatie\Permission\Models\Role::whereIn('name', ['Estudiante', 'student'])
            ->where('guard_name', 'sanctum')
            ->first();
        if (! $studentRole) {
            $studentRole = \Spatie\Permission\Models\Role::whereIn('name', ['Estudiante', 'student'])->first();
        }
        if ($studentRole) {
            // Pasar el modelo Role evita que Spatie busque por nombre con guard distinto
            $userEstudiante->assignRole($studentRole);
        } else {
            Log::warning('No existe el rol de estudiante, omitiendo assignRole', ['checked_roles'=>['Estudiante','student']]);
        }

        // 4. Mapear grado a curso según nivel Bolivia
        $curso = $this->mapGradoToCurso($preinscription->nivel, $preinscription->grado);

        // 5. Crear estudiante
        $student = Student::create([
            'user_id' => $userEstudiante->id,
            'codigo' => $codigoEstudiante,
            'usuario' => $uniqueUsuario,
            'password_temp' => $passwordTemp,
            'rude' => $preinscription->codigo_preinscripcion,
            'ci' => $preinscription->ci,
            'nombres' => $preinscription->nombres,
            'apellidos' => $preinscription->apellidos,
            'nivel' => $preinscription->nivel,
            'curso' => $curso,
            'paralelo' => 'A', // Por defecto, se puede cambiar después
            'anio_ingreso' => $year,
            'mes_ingreso' => date('m'),
            'fecha_nac' => $preinscription->fecha_nac,
            'sexo' => $preinscription->sexo,
            'email' => $preinscription->email,
            'telefono' => $preinscription->telefono,
            'activo' => true,
        ]);

            // 6. Crear o buscar padre/tutor por CI
            $parent = null;
            $parentPasswordTemp = null;

            if ($preinscription->tutor_ci) {
                // Buscar padre existente por CI
                $parent = ParentModel::where('ci', $preinscription->tutor_ci)->first();
            }

            if (!$parent) {
                // Crear nuevo padre/tutor
                $parentPasswordTemp = Str::random(8);

                // Generar email único si no hay
                $tutorEmail = $preinscription->email
                    ? 'tutor.' . $preinscription->email
                    : 'tutor.' . Str::slug($preinscription->tutor_nombre) . rand(100, 999) . '@colegio.edu.bo';

                // Crear usuario para el padre
                // Generar username base para tutor
                $tutorBase = $preinscription->tutor_ci ? 'tutor'.$preinscription->tutor_ci : Str::slug($preinscription->tutor_nombre, '');
                $tutorUsername = $tutorBase;
                $pCounter = 1;
                while (User::where('username', $tutorUsername)->exists()) {
                    $tutorUsername = $tutorBase . $pCounter++;
                }

                $userPadre = User::create([
                    'name' => $preinscription->tutor_nombre,
                    'email' => $tutorEmail,
                    'username' => $tutorUsername,
                    'password' => Hash::make($parentPasswordTemp),
                ]);
                // Asignar rol de padre/tutor; buscar rol con guard 'sanctum' y si no existe usar cualquier guard
                $parentRole = \Spatie\Permission\Models\Role::whereIn('name', ['Padre', 'parent', 'Tutor', 'tutor'])
                    ->where('guard_name', 'sanctum')
                    ->first();
                if (! $parentRole) {
                    $parentRole = \Spatie\Permission\Models\Role::whereIn('name', ['Padre', 'parent', 'Tutor', 'tutor'])->first();
                }
                if ($parentRole) {
                    $userPadre->assignRole($parentRole);
                } else {
                    Log::warning('No existe el rol de padre/tutor, omitiendo assignRole', ['checked_roles'=>['Padre','parent','Tutor','tutor']]);
                }

                // Crear registro de padre
                $parent = ParentModel::create([
                    'user_id' => $userPadre->id,
                    'ci' => $preinscription->tutor_ci,
                    'phone' => $preinscription->tutor_telefono,
                    'address' => $preinscription->direccion,
                ]);
            }

            // 7. Vincular padre con estudiante
            $relationship = $this->mapParentesco($preinscription->tutor_parentesco);
            $parent->students()->syncWithoutDetaching([
                $student->id => ['relationship' => $relationship]
            ]);

            // 8. Actualizar preinscripción
            $preinscription->update([
                'estado' => 'aprobado',
                'aprobado_por' => $request->user() ? $request->user()->id : null,
                'aprobado_at' => now(),
                'observaciones' => $request->observaciones,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Preinscripción aprobada exitosamente',
                'preinscription' => $preinscription,
                'student' => [
                    'id' => $student->id,
                    'codigo' => $student->codigo,
                    'usuario' => $student->usuario,
                    'password_temp' => $passwordTemp,
                ],
                'parent' => [
                    'id' => $parent->id,
                    'nombre' => $parent->user->name,
                    'ci' => $parent->ci,
                    'es_nuevo' => $parentPasswordTemp !== null,
                    'password_temp' => $parentPasswordTemp,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving preinscription', [
                'exception' => $e,
                'preinscription_id' => $id,
                'request_user' => $request->user() ? $request->user()->id : null,
            ]);
            return response()->json([
                'message' => 'Error al aprobar preinscripción: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mapear grado numérico a curso Bolivia
     */
    private function mapGradoToCurso($nivel, $grado)
    {
        $cursosInicial = ['1ra Sección', '2da Sección'];
        $cursosPrimaria = ['1ro Primaria', '2do Primaria', '3ro Primaria', '4to Primaria', '5to Primaria', '6to Primaria'];
        $cursosSecundaria = ['1ro Secundaria', '2do Secundaria', '3ro Secundaria', '4to Secundaria', '5to Secundaria', '6to Secundaria'];

        switch ($nivel) {
            case 'inicial':
                return $cursosInicial[$grado - 1] ?? $cursosInicial[0];
            case 'primaria':
                return $cursosPrimaria[$grado - 1] ?? $cursosPrimaria[0];
            case 'secundaria':
                return $cursosSecundaria[$grado - 1] ?? $cursosSecundaria[0];
            default:
                return '1ro Primaria';
        }
    }

    /**
     * Mapear parentesco a relación estándar
     */
    private function mapParentesco($parentesco)
    {
        $parentesco = strtolower(trim($parentesco));

        if (str_contains($parentesco, 'padre')) return 'padre';
        if (str_contains($parentesco, 'madre')) return 'madre';
        if (str_contains($parentesco, 'abuel')) return 'abuelo';
        if (str_contains($parentesco, 'tio') || str_contains($parentesco, 'tía')) return 'otro';
        if (str_contains($parentesco, 'tutor')) return 'tutor';

        return 'tutor';
    }

    /**
     * @OA\Patch(
     *     path="/api/preinscriptions/{id}/reject",
     *     summary="Rechazar preinscripción",
     *     description="Rechaza una preinscripción y guarda observaciones.",
     *     operationId="rejectPreinscription",
     *     tags={"Preinscriptions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de la preinscripción", @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=false, @OA\JsonContent(@OA\Property(property="observaciones", type="string"))),
     *     @OA\Response(response=200, description="Rechazado", @OA\JsonContent(ref="#/components/schemas/Preinscription")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function reject(Request $request, $id)
    {
        $this->authorize('approve', Preinscription::class);

        $preinscription = Preinscription::findOrFail($id);
        $preinscription->update([
            'estado' => 'rechazado',
            'observaciones' => $request->observaciones,
        ]);

        return response()->json($preinscription);
    }

    /**
     * @OA\Get(
     *     path="/api/preinscriptions/{codigo}/rude",
     *     summary="Descargar RUDE en PDF (público)",
     *     description="Genera y descarga el documento RUDE en formato PDF.",
     *     operationId="downloadRude",
     *     tags={"Preinscriptions"},
     *     security={},
     *     @OA\Parameter(name="codigo", in="path", required=true, description="Código de preinscripción", @OA\Schema(type="string")),
    *     @OA\Response(
    *         response=200,
    *         description="PDF generado",
    *         @OA\MediaType(
    *             mediaType="application/pdf",
    *             @OA\Schema(type="string", format="binary")
    *         )
    *     ),
     *     @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function downloadRude($codigo)
    {
        $preinscription = Preinscription::where('codigo_preinscripcion', $codigo)->firstOrFail();

        // Generar PDF del RUDE
        $pdf = Pdf::loadView('rude', compact('preinscription'));
        return $pdf->download('RUDE_' . $codigo . '.pdf');
    }
}
