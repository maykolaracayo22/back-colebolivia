<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/attendance",
     *     summary="Listar registros de asistencia",
     *     description="Obtiene lista paginada de asistencias con filtros opcionales",
     *     operationId="indexAttendance",
     *     tags={"Attendance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="section_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="subject_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="teacher_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="student_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="fecha", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Lista paginada de asistencias")
     * )
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Attendance::class);

        $q = Attendance::with(['student', 'section', 'subject', 'teacher'])
            ->when($request->student_id, fn($qq) => $qq->where('student_id', $request->student_id))
            ->when($request->section_id, fn($qq) => $qq->where('section_id', $request->section_id))
            ->when($request->subject_id, fn($qq) => $qq->where('subject_id', $request->subject_id))
            ->when($request->fecha, fn($qq) => $qq->where('fecha', $request->fecha))
            ->when($request->teacher_id, fn($qq) => $qq->where('teacher_id', $request->teacher_id))
            ->when(
                $request->user()->isTeacher() || $request->user()->hasRole(['teacher', 'Docente']),
                fn($qq) => $qq->where('teacher_id', $request->user()->teacher_id)
            )
            ->orderBy('fecha', 'desc')
            ->orderBy('student_id');

        return response()->json($q->paginate(50));
    }

    /**
     * @OA\Post(
     *     path="/api/attendance",
     *     summary="Registrar asistencia individual",
     *     description="Registra o actualiza la asistencia de un estudiante (upsert).",
     *     operationId="storeAttendance",
     *     tags={"Attendance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"student_id","section_id","subject_id","teacher_id","fecha","estado"},
     *             @OA\Property(property="student_id", type="integer", example=123),
     *             @OA\Property(property="section_id", type="integer", example=5),
     *             @OA\Property(property="subject_id", type="integer", example=10),
     *             @OA\Property(property="teacher_id", type="integer", example=42),
     *             @OA\Property(property="fecha", type="string", format="date", example="2024-11-29"),
     *             @OA\Property(property="estado", type="string", enum={"presente","ausente","tardanza","justificado"}),
     *             @OA\Property(property="observaciones", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Asistencia actualizada"),
     *     @OA\Response(response=201, description="Asistencia creada")
     * )
     */
    public function store(Request $request)
    {
        $this->authorize('create', Attendance::class);

        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'fecha' => 'required|date',
            'estado' => 'required|in:presente,ausente,tardanza,justificado',
            'observaciones' => 'nullable|string|max:255',
        ]);

        $attendance = Attendance::updateOrCreate(
            [
                'student_id' => $data['student_id'],
                'fecha' => $data['fecha'],
                'subject_id' => $data['subject_id'],
            ],
            $data
        );

        return response()->json($attendance, $attendance->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * @OA\Post(
     *     path="/api/attendance/bulk",
     *     summary="Registrar asistencia masiva",
     *     description="Registra asistencias de múltiples estudiantes.",
     *     operationId="bulkStoreAttendance",
     *     tags={"Attendance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"section_id","subject_id","teacher_id","fecha","attendances"},
     *             @OA\Property(property="section_id", type="integer"),
     *             @OA\Property(property="subject_id", type="integer"),
     *             @OA\Property(property="teacher_id", type="integer"),
     *             @OA\Property(property="fecha", type="string", format="date"),
     *             @OA\Property(
     *                 property="attendances",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="student_id", type="integer"),
     *                     @OA\Property(property="estado", type="string"),
     *                     @OA\Property(property="observaciones", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Asistencias registradas masivamente")
     * )
     */
    public function bulkStore(Request $request)
    {
        $this->authorize('create', Attendance::class);

        $data = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'fecha' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.estado' => 'required|in:presente,ausente,tardanza,justificado',
            'attendances.*.observaciones' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['attendances'] as $att) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $att['student_id'],
                        'fecha' => $data['fecha'],
                        'subject_id' => $data['subject_id'],
                    ],
                    [
                        'section_id' => $data['section_id'],
                        'teacher_id' => $data['teacher_id'],
                        'estado' => $att['estado'],
                        'observaciones' => $att['observaciones'] ?? null,
                    ]
                );
            }
        });

        return response()->json(['message' => 'Asistencias registradas exitosamente.']);
    }

    /**
     * @OA\Get(
     *     path="/api/attendance/{id}",
     *     summary="Mostrar registro de asistencia",
     *     operationId="showAttendance",
     *     tags={"Attendance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Detalles del registro"),
     *     @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function show(Attendance $attendance)
    {
        $this->authorize('view', $attendance);
        return response()->json($attendance->load(['student', 'section', 'subject', 'teacher']));
    }

    /**
     * @OA\Put(
     *     path="/api/attendance/{id}",
     *     summary="Actualizar registro de asistencia",
     *     operationId="updateAttendance",
     *     tags={"Attendance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="estado", type="string", enum={"presente","ausente","tardanza","justificado"}),
     *             @OA\Property(property="observaciones", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Asistencia actualizada")
     * )
     */
    public function update(Request $request, Attendance $attendance)
    {
        $this->authorize('update', $attendance);

        $data = $request->validate([
            'estado' => 'sometimes|in:presente,ausente,tardanza,justificado',
            'observaciones' => 'nullable|string|max:255',
        ]);

        $attendance->update($data);
        return response()->json($attendance);
    }

    /**
     * @OA\Delete(
     *     path="/api/attendance/{id}",
     *     summary="Eliminar registro de asistencia",
     *     operationId="destroyAttendance",
     *     tags={"Attendance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Asistencia eliminada")
     * )
     */
    public function destroy(Attendance $attendance)
    {
        $this->authorize('delete', $attendance);
        $attendance->delete();
        return response()->json(['deleted' => true]);
    }


    /**
     * @OA\Get(
     *     path="/api/attendance/report/{studentId}",
     *     summary="Reporte de asistencia por estudiante",
     *     description="Genera un reporte completo de asistencias de un estudiante con estadÃƒÆ’Ã‚Â­sticas de presente, ausente, tardanza y justificado.",
     *     operationId="reportAttendance",
     *     tags={"Attendance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="studentId",
     *         in="path",
     *         description="ID del estudiante",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reporte de asistencia generado",
     *         @OA\JsonContent(
     *             @OA\Property(property="student_id", type="integer"),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="presente", type="integer"),
     *             @OA\Property(property="ausente", type="integer"),
     *             @OA\Property(property="tardanza", type="integer"),
     *             @OA\Property(property="justificado", type="integer"),
     *             @OA\Property(property="porcentaje_asistencia", type="number", format="float")
     *         )
     *     )
     * )
     */
// Reporte de asistencia por estudiante
    public function myReport(Request $request)
    {
        $user = $request->user();
        $student = $user->student ?: Student::where('user_id', $user->id)->first();
        abort_unless($student, 404, 'La cuenta no está vinculada a un perfil de estudiante.');

        return $this->report($request, $student->id);
    }

    public function report(Request $request, $studentId)
    {
        $student = Student::findOrFail($studentId);
        $user = $request->user();
        $linkedStudentId = $user->student_id ?: optional($user->student)->id;
        $canView = (int) $student->user_id === (int) $user->id
            || ($linkedStudentId && (int) $student->id === (int) $linkedStudentId)
            || $user->hasRole(['Admin', 'SuperAdmin', 'Director General', 'director_academico', 'regente']);
        if (!$canView && $user->hasRole('parent')) {
            $canView = (string) $student->tutor_ci !== ''
                && (string) $student->tutor_ci === (string) optional($user->parent)->ci;
        }
        abort_unless($canView, 403, 'No autorizado para consultar estas asistencias.');

        $q = Attendance::where('student_id', $studentId)
            ->when($request->fecha_desde, fn($qq) => $qq->where('fecha', '>=', $request->fecha_desde))
            ->when($request->fecha_hasta, fn($qq) => $qq->where('fecha', '<=', $request->fecha_hasta))
            ->with(['subject', 'teacher']);

        $attendances = $q->orderByDesc('fecha')->get();

        $summary = [
            'total_clases' => $attendances->count(),
            'presentes' => $attendances->where('estado', 'presente')->count(),
            'ausentes' => $attendances->where('estado', 'ausente')->count(),
            'tardanzas' => $attendances->where('estado', 'tardanza')->count(),
            'justificados' => $attendances->where('estado', 'justificado')->count(),
            'porcentaje_asistencia' => $attendances->count() > 0 ? round(($attendances->whereIn('estado', ['presente', 'justificado'])->count() / $attendances->count()) * 100, 2) : 0,
        ];

        return response()->json([
            'student' => $student,
            'attendances' => $attendances,
            'summary' => $summary,
        ]);
    }
}
