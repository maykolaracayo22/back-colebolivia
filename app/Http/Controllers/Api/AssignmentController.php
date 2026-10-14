<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/assignments",
     *     summary="Listar asignaciones docente-materia-curso",
     *     description="Obtiene todas las asignaciones de docentes a materias y cursos. Los docentes solo ven sus propias asignaciones.",
     *     operationId="getAssignments",
     *     tags={"Assignments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="year", in="query", required=false, @OA\Schema(type="integer", example=2024)),
     *     @OA\Parameter(name="gestion", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="section_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="subject_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="teacher_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="estado", in="query", required=false, @OA\Schema(type="string", enum={"ACTIVO","INACTIVO"})),
     *     @OA\Response(response=200, description="Lista de asignaciones", @OA\JsonContent(type="object", @OA\Property(property="success", type="boolean", example=true), @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Assignment"))))
     * )
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Assignment::class);

        $query = Assignment::with(['section', 'subject', 'teacher']);

        $user = $request->user();
        $userRole = strtolower(str_replace(['_', '-'], ' ', (string) $user->role));
        $isTeacher = in_array($userRole, ['teacher', 'docente'], true)
            || (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['teacher', 'Docente']));

        if ($isTeacher) {
            $query->where('teacher_id', $user->teacher_id);
        }

        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        if ($request->has('nivel')) {
            $nivel = strtolower($request->query('nivel'));
            $query->whereHas('section', function ($sectionQuery) use ($nivel) {
                $sectionQuery->where('nivel', $nivel);
            });
        }

        if ($request->has('gestion')) {
            $query->where('gestion', $request->gestion);
        }

        if ($request->has('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/assignments",
     *     summary="Crear asignación",
     *     tags={"Assignments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"year","section_id","subject_id","teacher_id"}, @OA\Property(property="year", type="integer"), @OA\Property(property="section_id", type="integer"), @OA\Property(property="subject_id", type="integer"), @OA\Property(property="teacher_id", type="integer"))),
     *     @OA\Response(response=201, description="Asignación creada", @OA\JsonContent(ref="#/components/schemas/Assignment"))
     * )
     */
    public function store(Request $request)
    {
        $this->authorize('create', Assignment::class);
        $validated = $request->validate([
            'year'       => 'required|integer',
            'gestion'    => 'nullable|string|max:4',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'dia'        => 'nullable|string',
            'inicio'     => 'nullable|date_format:H:i',
            'fin'        => 'nullable|date_format:H:i',
            'aula'       => 'nullable|string',
            'titular'    => 'nullable|boolean',
            'estado'     => 'nullable|in:ACTIVO,INACTIVO',
        ]);
        $assignment = Assignment::create($validated);


        return response()->json(['success' => true, 'message' => 'Asignación creada', 'data' => $assignment->load(['section', 'subject', 'teacher'])], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/assignments/{id}",
     *     summary="Ver asignación",
     *     tags={"Assignments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalles", @OA\JsonContent(ref="#/components/schemas/Assignment"))
     * )
     */
    public function show(Request $request, $id)
    {
        $assignment = Assignment::with(['section', 'subject', 'teacher'])->findOrFail($id);
        $this->authorize('view', $assignment);
        return response()->json(['success' => true, 'data' => $assignment]);
    }

    /**
     * @OA\Put(
     *     path="/api/assignments/{id}",
     *     summary="Actualizar asignación",
     *     tags={"Assignments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(@OA\Property(property="year", type="integer"), @OA\Property(property="section_id", type="integer"), @OA\Property(property="estado", type="string", enum={"ACTIVO","INACTIVO"}))),
     *     @OA\Response(response=200, description="Actualizada", @OA\JsonContent(ref="#/components/schemas/Assignment"))
     * )
     */
    public function update(Request $request, $id)
    {
        $assignment = Assignment::findOrFail($id);
        $this->authorize('update', $assignment);
        $validated = $request->validate([
            'year'       => 'required|integer',
            'gestion'    => 'nullable|string|max:4',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'dia'        => 'nullable|string',
            'inicio'     => 'nullable|date_format:H:i',
            'fin'        => 'nullable|date_format:H:i',
            'aula'       => 'nullable|string',
            'titular'    => 'nullable|boolean',
            'estado'     => 'nullable|in:ACTIVO,INACTIVO',
        ]);

        $assignment->update($validated);
        return response()->json(['success' => true, 'message' => 'Actualizada', 'data' => $assignment->load(['section', 'subject', 'teacher'])]);
    }

    /**
     * @OA\Delete(
     *     path="/api/assignments/{id}",
     *     summary="Eliminar asignación",
     *     tags={"Assignments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Eliminada", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="message", type="string")))
     * )
     */
    public function destroy(Request $request, $id)
    {
        $assignment = Assignment::findOrFail($id);
        $this->authorize('delete', $assignment);
        $assignment->delete();
        return response()->json(['success' => true, 'message' => 'Eliminada']);
    }
}
