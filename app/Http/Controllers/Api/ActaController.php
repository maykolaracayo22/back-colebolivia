<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Acta;
use App\Models\CentralizadorLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Services\GradeService;
use App\Models\ActaNote;
use App\Models\ActaTermTotal;
use App\Http\Requests\SaveItemsRequest;

/**
 * @OA\Schema(
 *     schema="Acta",
 *     type="object",
 *     required={"year","nivel","grado","seccion","term","materia_id"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="year", type="integer", example=2024),
 *     @OA\Property(property="nivel", type="string", enum={"inicial","primaria","secundaria"}, example="secundaria"),
 *     @OA\Property(property="grado", type="integer", minimum=1, maximum=12, example=6),
 *     @OA\Property(property="seccion", type="string", maxLength=5, example="A"),
 *     @OA\Property(property="term", type="string", enum={"T1","T2","T3"}, example="T1"),
 *     @OA\Property(property="materia_id", type="integer", example=5),
 *     @OA\Property(property="materia_nombre", type="string", example="MatemÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ticas"),
 *     @OA\Property(property="docente_id", type="integer", example=10),
 *     @OA\Property(property="docente_nombre", type="string", example="Juan PÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©rez"),
 *     @OA\Property(property="status", type="string", enum={"DRAFT","LOCKED","PUBLISHED"}, example="DRAFT"),
 *     @OA\Property(property="ref", type="string", example="2024:A:5:T1"),
 *     @OA\Property(property="etag", type="string", format="uuid"),
 *     @OA\Property(property="edit_window_ends", type="string", format="date-time"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

class ActaController extends Controller
{
    /** Lista solo las actas del docente autenticado para lectura del libro de notas. */
    public function myGradebookIndex(Request $request)
    {
        $teacherId = $request->user()->teacher_id ?? optional($request->user()->teacher)->id;
        if (!$teacherId) {
            return response()->json(['message' => 'No hay un perfil docente vinculado a este usuario.'], 403);
        }

        $query = Acta::query()
            ->where('docente_id', $teacherId)
            ->when($request->year, fn ($q, $value) => $q->where('year', $value))
            ->when($request->nivel, fn ($q, $value) => $q->where('nivel', $value))
            ->when($request->grado, fn ($q, $value) => $q->where('grado', $value))
            ->when($request->seccion, fn ($q, $value) => $q->where('seccion', $value))
            ->when($request->term, fn ($q, $value) => $q->where('term', $value))
            ->when($request->materia_id, fn ($q, $value) => $q->where('materia_id', $value))
            ->orderByDesc('updated_at');

        return response()->json($query->paginate(min(max((int) $request->query('per_page', 200), 1), 200)));
    }

    /** Muestra un acta propia del docente, incluyendo notas y totales. */
    public function myGradebookShow(Request $request, Acta $acta)
    {
        $teacherId = $request->user()->teacher_id ?? optional($request->user()->teacher)->id;
        if (!$teacherId || (int) $acta->docente_id !== (int) $teacherId) {
            return response()->json(['message' => 'No tienes acceso a esta acta.'], 403);
        }

        $acta->load(['evaluations.notes', 'termTotals']);
        return response()->json($acta);
    }

    /**
     * GET /api/actas?year=&nivel=&grado=&seccion=&term=&materia_id=
     */
    /**
     * @OA\Get(
     *     path="/api/actas",
     *     summary="Listar actas",
     *     description="Obtiene un listado paginado de actas con filtros opcionales. Los docentes solo ven sus propias actas.",
     *     operationId="getActas",
     *     tags={"Actas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="year", in="query", description="AÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â±o de gestiÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n", required=false, @OA\Schema(type="integer", example=2024)),
     *     @OA\Parameter(name="nivel", in="query", description="Nivel educativo", required=false, @OA\Schema(type="string", enum={"inicial","primaria","secundaria"})),
     *     @OA\Parameter(name="grado", in="query", description="Grado", required=false, @OA\Schema(type="integer", minimum=1, maximum=12)),
     *     @OA\Parameter(name="seccion", in="query", description="SecciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="term", in="query", description="TÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©rmino", required=false, @OA\Schema(type="string", enum={"T1","T2","T3"})),
     *     @OA\Parameter(name="materia_id", in="query", description="ID de la materia", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Lista de actas paginada"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */

    public function index(Request $req)
    {
        $this->authorize('viewAny', Acta::class);

        $q = Acta::query()
            ->when($req->year, fn($qq,$v)=>$qq->where('year',$v))
            ->when($req->nivel, fn($qq,$v)=>$qq->where('nivel',$v))
            ->when($req->grado, fn($qq,$v)=>$qq->where('grado',$v))
            ->when($req->seccion, fn($qq,$v)=>$qq->where('seccion',$v))
            ->when($req->term, fn($qq,$v)=>$qq->where('term',$v))
            ->when($req->materia_id, fn($qq,$v)=>$qq->where('materia_id',$v))
            ->orderByDesc('updated_at');

        // Si es docente, mostrar solo sus actas
        if ($req->user()->hasAnyRole(['teacher', 'Docente'])) {
            $docenteId = $req->user()->teacher_id ?? optional($req->user()->teacher)->id;
            $q->where('docente_id', $docenteId ?? -1);
        }

        return response()->json($q->paginate(20));
    }

    /**
     * POST /api/actas
     */
    /**
     * @OA\Post(
     *     path="/api/actas",
     *     summary="Crear nueva acta",
     *     description="Crea una nueva acta de calificaciones para una materia en un tÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©rmino especÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­fico. Valida que no exista duplicado.",
     *     operationId="createActa",
     *     tags={"Actas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"year","nivel","grado","seccion","term","materia_id","materia_nombre","docente_id","docente_nombre"},
     *             @OA\Property(property="year", type="integer", example=2024),
     *             @OA\Property(property="nivel", type="string", enum={"inicial","primaria","secundaria"}, example="secundaria"),
     *             @OA\Property(property="grado", type="integer", minimum=1, maximum=12, example=6),
     *             @OA\Property(property="seccion", type="string", maxLength=5, example="A"),
     *             @OA\Property(property="term", type="string", enum={"T1","T2","T3"}, example="T1"),
     *             @OA\Property(property="materia_id", type="integer", example=5),
     *             @OA\Property(property="materia_nombre", type="string", example="MatemÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ticas"),
     *             @OA\Property(property="docente_id", type="integer", example=10),
     *             @OA\Property(property="docente_nombre", type="string", example="Juan PÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©rez")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Acta creada exitosamente", @OA\JsonContent(ref="#/components/schemas/Acta")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=422, description="ValidaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n fallida o acta duplicada")
     * )
     */
    public function store(Request $req)
    {
        $this->authorize('create', Acta::class);

        // 👇 AHORA solo validamos lo mínimo que viene del body
        $data = $req->validate([
            'year'         => ['required', 'digits:4'],
            'assignment_id'=> ['required', 'exists:assignments,id'],
            'section_id'   => ['required', 'exists:sections,id'],
            'period_id'    => ['required', 'exists:periods,id'],
        ]);

        // Cargamos la asignación con sus relaciones
        $assignment = \App\Models\Assignment::with(['section','subject','teacher'])
            ->findOrFail($data['assignment_id']);

        $period  = \App\Models\Period::findOrFail($data['period_id']);
        $section = $assignment->section;
        $subject = $assignment->subject;
        $teacher = $assignment->teacher;

        if (!$section || !$subject || !$teacher) {
            return response()->json([
                'message' => 'La asignación debe tener sección, materia y docente asociados'
            ], 422);
        }

        if ((int) $assignment->section_id !== (int) $section->id ||
            (int) $assignment->section_id !== (int) $data['section_id'] ||
            (int) $assignment->year !== (int) $data['year'] ||
            (int) $section->year !== (int) $data['year'] ||
            (int) $period->year !== (int) $data['year'] ||
            $assignment->estado !== 'ACTIVO') {
            return response()->json(['message' => 'La asignación, sección y período deben corresponder a la misma gestión.'], 422);
        }

        $user = $req->user();
        if ($user->hasAnyRole(['teacher', 'Docente']) && (int) $assignment->teacher_id !== (int) $user->teacher_id) {
            return response()->json(['message' => 'Solo puedes crear actas para tus propias asignaciones.'], 403);
        }

        // 🧠 Autocompletar datos del acta
        $actaData = [
            'year'           => (int) $data['year'],
            'nivel'          => $section->nivel,
            'grado'          => intval($section->curso),     // "2do" → 2
            'seccion'        => $section->paralelo,
            'term'           => $period->code,               // T1 / T2 / T3
            'materia_id'     => $subject->id,
            'materia_nombre' => $subject->nombre,
            'docente_id'     => $teacher->id,
            'docente_nombre' => $teacher->nombres.' '.$teacher->apellidos,
            'status'         => 'DRAFT',
            'etag'           => (string) Str::uuid(),
            'edit_window_ends' => now()->addDays(30),
        ];

        // REF único
        $actaData['ref'] = "{$actaData['year']}:{$actaData['seccion']}:{$actaData['materia_id']}:{$actaData['term']}";

        // Evitar duplicado por scope
        $exists = Acta::where([
            'year'       => $actaData['year'],
            'nivel'      => $actaData['nivel'],
            'grado'      => $actaData['grado'],
            'seccion'    => $actaData['seccion'],
            'materia_id' => $actaData['materia_id'],
            'term'       => $actaData['term'],
        ])->exists();

        if ($exists) {
            return response()->json(['message' => 'Ya existe un acta para ese scope.'], 422);
        }

        $acta = Acta::create($actaData);

        return response()->json($acta, 201);
    }


    /**
     * GET /api/actas/{id}
     */
    /**
     * @OA\Get(
     *     path="/api/actas/{id}",
     *     summary="Ver detalle de acta",
     *     description="Obtiene los detalles completos de un acta especÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­fica.",
     *     operationId="getActa",
     *     tags={"Actas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del acta", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalle del acta", @OA\JsonContent(ref="#/components/schemas/Acta")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Acta no encontrada")
     * )
     */
    public function show(Acta $acta)
    {
        $this->authorize('view', $acta);
        $acta->load(['evaluations.notes', 'termTotals']);
        return response()->json($acta);
    }

    /**
     * PUT /api/actas/{id}/items
     * Bulk save notes, autos and PE. Payload: { notes: [...], auto: [...], pe: [...] }
     */
    /**
     * @OA\Put(
     *     path="/api/actas/{id}/items",
     *     summary="Guardar notas masivamente",
     *     description="Guarda mÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âºltiples notas, autoevaluaciones y PE en lote. Valida ventana de ediciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n y estado del acta.",
     *     operationId="saveActaItems",
     *     tags={"Actas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del acta", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="notes", type="array", @OA\Items(
     *                 @OA\Property(property="student_id", type="integer"),
     *                 @OA\Property(property="evaluation_id", type="integer"),
     *                 @OA\Property(property="valor", type="number", format="float")
     *             )),
     *             @OA\Property(property="auto", type="array", @OA\Items(
     *                 @OA\Property(property="student_id", type="integer"),
     *                 @OA\Property(property="valor", type="number", format="float")
     *             )),
     *             @OA\Property(property="pe", type="array", @OA\Items(
     *                 @OA\Property(property="student_id", type="integer"),
     *                 @OA\Property(property="valor", type="number", format="float")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Items guardados exitosamente"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=422, description="Acta publicada o ventana de ediciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n expirada")
     * )
     */
    public function saveItems(SaveItemsRequest $req, $id, GradeService $grades)
    {
        $acta = Acta::findOrFail($id);
        // SaveItemsRequest handles authorization and validation
        $this->authorize('update', $acta);

        // Validar que el acta no estÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ publicada
        if ($acta->status === 'PUBLISHED') {
            return response()->json([
                'message' => 'No se puede editar un acta publicada. Debe solicitar rectificaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n.'
            ], 422);
        }

        // Validar ventana de ediciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n
        if ($acta->edit_window_ends && $acta->edit_window_ends < now() && $acta->status !== 'DRAFT') {
            return response()->json([
                'message' => 'La ventana de ediciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n ha expirado. Debe solicitar permisos especiales.'
            ], 422);
        }

        $payload = $req->validated();

        DB::transaction(function() use ($payload, $acta) {
            // notes upsert
            if (!empty($payload['notes'])) {
                foreach ($payload['notes'] as $n) {
                    // support both 'evaluation_id' (tests/payload) and 'criterio_id' (legacy)
                    $evaluationId = $n['evaluation_id'] ?? $n['criterio_id'] ?? null;
                    ActaNote::updateOrCreate([
                        'acta_id' => $acta->id,
                        'student_id' => $n['student_id'],
                        'evaluation_id' => $evaluationId,
                    ], [
                        'tipo' => 'NUM',
                        'valor' => $n['valor'] ?? null,
                    ]);
                }
            }

            // auto
            if (!empty($payload['auto'])) {
                foreach ($payload['auto'] as $a) {
                    ActaTermTotal::updateOrCreate([
                        'acta_id' => $acta->id,
                        'student_id' => $a['student_id']
                    ], [ 'autoevaluacion' => $a['valor'] ]);
                }
            }

            // pe
            if (!empty($payload['pe'])) {
                foreach ($payload['pe'] as $p) {
                    ActaTermTotal::updateOrCreate([
                        'acta_id' => $acta->id,
                        'student_id' => $p['student_id']
                    ], [ 'pe' => $p['valor'] ]);
                }
            }
        });

        // recompute aggregates after save
        $grades->recomputeActa($acta);

        return response()->json(['message'=>'OK']);
    }

    /**
     * POST /api/actas/{id}/recompute
     */
    /**
     * @OA\Post(
     *     path="/api/actas/{id}/recompute",
     *     summary="Recalcular totales",
     *     description="Recalcula los totales TN (TÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©rmino NumÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©rico) de todos los estudiantes del acta.",
     *     operationId="recomputeActa",
     *     tags={"Actas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del acta", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Totales recalculados"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Acta no encontrada")
     * )
     */
    public function recompute($id, GradeService $grades)
    {
        $acta = Acta::findOrFail($id);
        $this->authorize('view', $acta);

        $grades->recomputeActa($acta);
        return response()->json(['message'=>'recomputed']);
    }

    /**
     * PUT /api/actas/{id}
     * Soporta ETag con If-Match.
     */
    /**
     * @OA\Put(
     *     path="/api/actas/{id}",
     *     summary="Actualizar metadatos del acta",
     *     description="Actualiza los nombres de materia y docente. Soporta concurrencia optimista con If-Match/ETag.",
     *     operationId="updateActa",
     *     tags={"Actas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del acta", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="If-Match", in="header", required=false, description="ETag para concurrencia optimista", @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="materia_nombre", type="string", maxLength=255),
     *             @OA\Property(property="docente_nombre", type="string", maxLength=255)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Acta actualizada", @OA\JsonContent(ref="#/components/schemas/Acta")),
     *     @OA\Response(response=400, description="Nada que actualizar"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=412, description="ETag no coincide (precondition failed)")
     * )
     */
    public function update(Request $req, Acta $acta)
    {
        $this->authorize('update', $acta);

        // Concurrencia optimista
        if ($ifMatch = $req->header('If-Match')) {
            if (!$acta->etag || $acta->etag !== $ifMatch) {
                return response()->json(['message'=>'ETag no coincide (precondition failed).'], 412);
            }
        }

        $data = $req->validate([
            'materia_nombre' => ['sometimes','string','max:255'],
            'docente_nombre' => ['sometimes','string','max:255'],
            // No dejamos cambiar scope duro por simplicidad. Agrega si lo necesitas.
        ]);

        if (empty($data)) {
            return response()->json(['message'=>'Nada que actualizar.'], 400);
        }

        $acta->fill($data);
        $acta->etag = (string) Str::uuid();
        $acta->save();

        return response()->json($acta);
    }

    /**
     * DELETE /api/actas/{id}
     */
    /**
     * @OA\Delete(
     *     path="/api/actas/{id}",
     *     summary="Eliminar acta",
     *     description="Elimina un acta. Solo permitido para administradores y actas en estado DRAFT.",
     *     operationId="deleteActa",
     *     tags={"Actas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del acta", @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Acta eliminada exitosamente"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Acta no encontrada")
     * )
     */
    public function destroy(Acta $acta)
    {
        $this->authorize('delete', $acta);
        $acta->delete();
        return response()->json(['message'=>'OK'], 204);
    }

    /**
     * POST /api/actas/{id}/lock
     * Cambia DRAFT -> LOCKED
     */
    /**
     * @OA\Post(
     *     path="/api/actas/{id}/lock",
     *     summary="Bloquear acta",
     *     description="Cambia el estado del acta de DRAFT a LOCKED, impidiendo ediciones no autorizadas.",
     *     operationId="lockActa",
     *     tags={"Actas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del acta", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Acta bloqueada exitosamente", @OA\JsonContent(ref="#/components/schemas/Acta")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=422, description="Solo se puede bloquear una acta en DRAFT")
     * )
     */
    public function lock($id)
    {
        $acta = Acta::findOrFail($id);
        $this->authorize('lock', $acta);

        if ($acta->status !== 'DRAFT') {
            return response()->json(['message'=>'Solo se puede bloquear una acta en DRAFT.'], 422);
        }

        $acta->status = 'LOCKED';
        $acta->locked_at = now();
        $acta->locked_by = auth()->id();
        $acta->etag = (string) Str::uuid();
        $acta->save();

        return response()->json($acta);
    }

    /**
     * POST /api/actas/{id}/publish
     * Cambia LOCKED -> PUBLISHED
     * Valida que todo estÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â© completo antes de publicar
     */
    /**
     * @OA\Post(
     *     path="/api/actas/{id}/publish",
     *     summary="Publicar acta",
     *     description="Cambia el estado del acta de LOCKED a PUBLISHED. Valida completitud (pesos=100, notas completas) antes de publicar.",
     *     operationId="publishActa",
     *     tags={"Actas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del acta", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Acta publicada exitosamente", @OA\JsonContent(ref="#/components/schemas/Acta")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado - requiere permiso notas.consolidar"),
     *     @OA\Response(response=422, description="Solo se puede publicar una acta en LOCKED o no cumple requisitos de completitud")
     * )
     */
    public function publish($id, \App\Services\GradeService $grades)
    {
        $acta = Acta::findOrFail($id);
        $this->authorize('publish', $acta);

        if ($acta->status !== 'LOCKED') {
            return response()->json(['message'=>'Solo se puede publicar una acta en LOCKED.'], 422);
        }

        // Validar antes de publicar
        $validacion = $this->validarCompletitud($acta);

        if (!$validacion['valido']) {
            return response()->json([
                'message' => 'El acta no cumple con los requisitos para ser publicada',
                'errores' => $validacion['errores'],
                'advertencias' => $validacion['advertencias']
            ], 422);
        }

        // Recompute before publish to ensure centralizador has latest TN/PA
        $grades->recomputeActa($acta);

        // Actualizar centralizador con las notas publicadas
        $this->updateCentralizador($acta);

        $acta->status = 'PUBLISHED';
        $acta->published_at = now();
        $acta->published_by = auth()->id();
        $acta->etag = (string) Str::uuid();
        $acta->save();

        return response()->json($acta);
    }

    /**
     * POST /api/actas/{id}/validate
     * Valida completitud del acta (pesos, notas, etc.)
     */
    /**
     * @OA\Post(
     *     path="/api/actas/{id}/validate",
     *     summary="Validar acta",
     *     description="Valida la completitud del acta: suma de pesos=100, todas las notas registradas, TN calculados. Retorna errores y advertencias.",
     *     operationId="validateActa",
     *     tags={"Actas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del acta", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Resultado de validaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n",
     *         @OA\JsonContent(
     *             @OA\Property(property="ref", type="string"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="valido", type="boolean"),
     *             @OA\Property(property="errores", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="advertencias", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="estadisticas", type="object",
     *                 @OA\Property(property="evaluaciones", type="integer"),
     *                 @OA\Property(property="suma_pesos", type="number"),
     *                 @OA\Property(property="estudiantes", type="integer"),
     *                 @OA\Property(property="notas_esperadas", type="integer"),
     *                 @OA\Property(property="notas_registradas", type="integer"),
     *                 @OA\Property(property="completitud_porcentaje", type="number"),
     *                 @OA\Property(property="estudiantes_sin_tn", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Acta no encontrada")
     * )
     */
    public function validateActa($id)
    {
        $acta = Acta::findOrFail($id);
        $this->authorize('view', $acta);

        $validacion = $this->validarCompletitud($acta);

        return response()->json([
            'ref' => $acta->ref,
            'status' => $acta->status,
            'valido' => $validacion['valido'],
            'errores' => $validacion['errores'],
            'advertencias' => $validacion['advertencias'],
            'estadisticas' => $validacion['estadisticas']
        ]);
    }

    /**
     * MÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©todo privado para validar completitud del acta
     */
    private function validarCompletitud($acta)
    {
        $errores = [];
        $advertencias = [];
        $valido = true;

        // 1. Validar que existen evaluaciones (criterios)
        $evaluaciones = $acta->evaluaciones()->get();

        if ($evaluaciones->isEmpty()) {
            $errores[] = 'El acta no tiene evaluaciones/criterios definidos';
            $valido = false;
        }

        // 2. Validar suma de pesos = 100
        if ($evaluaciones->isNotEmpty()) {
            $sumaPesos = $evaluaciones->sum(function($eval) {
                return $eval->weight ?? $eval->peso ?? 0;
            });

            if ((int)$sumaPesos !== 100) {
                $errores[] = "La suma de pesos es {$sumaPesos}, debe ser 100";
                $valido = false;
            }
        }

        // 3. Validar que hay estudiantes en el acta
        $termTotals = ActaTermTotal::where('acta_id', $acta->id)->get();
        $studentIds = $termTotals->pluck('student_id')->unique();

        if ($studentIds->isEmpty()) {
            $errores[] = 'El acta no tiene estudiantes asignados';
            $valido = false;
        }

        // 4. Validar que todas las notas estÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡n completas
        $totalNotasEsperadas = $studentIds->count() * $evaluaciones->count();
        $notasRegistradas = ActaNote::where('acta_id', $acta->id)
            ->whereNotNull('valor')
            ->count();

        $porcentajeCompletitud = $totalNotasEsperadas > 0
            ? round(($notasRegistradas / $totalNotasEsperadas) * 100, 2)
            : 0;

        if ($porcentajeCompletitud < 100) {
            $errores[] = "Faltan notas por registrar. Completitud: {$porcentajeCompletitud}%";
            $valido = false;
        } elseif ($porcentajeCompletitud < 95) {
            $advertencias[] = "Completitud de notas: {$porcentajeCompletitud}%. Revisar notas faltantes.";
        }

        // 5. Validar que todos los TN estÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡n calculados
        $sinTN = $termTotals->where('tn', null)->count();
        if ($sinTN > 0) {
            $advertencias[] = "Hay {$sinTN} estudiantes sin TN calculado. Ejecutar recompute.";
        }

        // 6. Verificar ventana de ediciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n (advertencia)
        if ($acta->edit_window_ends && $acta->edit_window_ends < now()) {
            $advertencias[] = 'La ventana de ediciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n ha expirado';
        }

        return [
            'valido' => $valido,
            'errores' => $errores,
            'advertencias' => $advertencias,
            'estadisticas' => [
                'evaluaciones' => $evaluaciones->count(),
                'suma_pesos' => $evaluaciones->sum(fn($e) => $e->weight ?? $e->peso ?? 0),
                'estudiantes' => $studentIds->count(),
                'notas_esperadas' => $totalNotasEsperadas,
                'notas_registradas' => $notasRegistradas,
                'completitud_porcentaje' => $porcentajeCompletitud,
                'estudiantes_sin_tn' => $sinTN
            ]
        ];
    }


/**
     * Actualizar centralizador con las notas del acta publicada
     */
    private function updateCentralizador(Acta $acta): void
    {
        // Obtener todas las notas finales (TN) de los estudiantes
        $termTotals = ActaTermTotal::where('acta_id', $acta->id)->get();

        // Extraer el nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âºmero del tÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©rmino (1, 2 o 3) desde T1, T2, T3
        $termNumber = (int) str_replace('T', '', $acta->term);
        $termField = 't' . $termNumber;

        foreach ($termTotals as $total) {
            if ($total->student_id && $total->tn !== null) {
                // Datos base para identificar la lÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­nea ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âºnica
                $baseData = [
                    'year' => $acta->year,
                    'materia_id' => $acta->materia_id,
                    'student_id' => $total->student_id,
                ];

                // Datos adicionales para crear/actualizar
                $additionalData = [
                    'nivel' => $acta->nivel,
                    'grado' => $acta->grado,
                    'seccion' => $acta->seccion,
                    'section_id' => null, // TODO: obtener section_id si existe
                    $termField => (int) $total->tn,
                ];

                // Crear o actualizar la lÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­nea
                $line = CentralizadorLine::updateOrCreate($baseData, $additionalData);

                // Recalcular PA si existen los 3 trimestres
                $line->recalculatePA();

                // Actualizar estado si todos los tÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©rminos estÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡n completos
                if ($line->t1 !== null && $line->t2 !== null && $line->t3 !== null) {
                    $line->update([
                        'status' => 'COMPLETE',
                        'published_at' => now(),
                        'published_by' => auth()->id(),
                    ]);
                }
            }
        }
    }
}
