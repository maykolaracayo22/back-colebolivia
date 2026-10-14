<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Assignment;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class MeController extends Controller
{
    protected function meStudent()
    {
        $user = auth()->user();
        return Student::where('user_id', $user->id)->firstOrFail();
    }/**
     * @OA\Get(
     *     path="/api/me/subjects",
     *     summary="Mis materias",
     *     description="Obtiene las materias asignadas al estudiante autenticado",
     *     operationId="getMySubjects",
     *     tags={"Perfil"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de materias del estudiante",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nombre", type="string", example="Matemáticas")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Estudiante no encontrado")
     * )
     */
    public function subjects(Request $req)
    {
        $st = $this->meStudent();
        // Por simplicidad: materias por secciÃƒÂ³n actual (si tuvieras enrollments por gestiÃƒÂ³n, filtra por year)
        $sectionId = DB::table('enrollments')->where('student_id',$st->id)->value('section_id');
        $subjects = DB::table('assignments')
            ->join('subjects','subjects.id','=','assignments.subject_id')
            ->where('assignments.section_id',$sectionId)
            ->select('subjects.id','subjects.nombre')
            ->distinct()->get();
        return response()->json(['data'=>$subjects]);
    }

    /**
     * @OA\Get(
     *     path="/api/me/grades",
     *     summary="Mis notas",
     *     description="Obtiene todas las notas del estudiante autenticado por acta y evaluación",
     *     operationId="getMyGrades",
     *     tags={"Perfil"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de notas del estudiante",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="acta_id", type="integer"),
     *                     @OA\Property(property="term", type="string", example="T1"),
     *                     @OA\Property(property="subject_nombre", type="string", example="Matemáticas"),
     *                     @OA\Property(property="evaluacion", type="string", example="Examen Final"),
     *                     @OA\Property(property="peso", type="number", example=40),
     *                     @OA\Property(property="tipo", type="string", example="NUM"),
     *                     @OA\Property(property="valor", type="number", example=85),
     *                     @OA\Property(property="valor_qual", type="string", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function grades(Request $req)
    {
        $st = $this->meStudent();
        // Notas por acta/ evaluaciÃƒÂ³n del estudiante
        $rows = DB::table('acta_notes')
            ->join('acta_evaluations','acta_evaluations.id','=','acta_notes.evaluation_id')
            ->join('actas','actas.id','=','acta_notes.acta_id')
            ->where('acta_notes.student_id',$st->id)
            ->select('actas.id as acta_id','actas.term','actas.subject_nombre','acta_evaluations.nombre as evaluacion',
                     'acta_evaluations.peso','acta_notes.tipo','acta_notes.valor','acta_notes.valor_qual')
            ->orderBy('actas.id')->orderBy('acta_evaluations.id')
            ->get();
        return response()->json(['data'=>$rows]);
    }

    /**
     * @OA\Get(
     *     path="/api/me/attendance",
     *     summary="Mi asistencia",
     *     description="Obtiene el registro de asistencia del estudiante autenticado",
     *     operationId="getMyAttendance",
     *     tags={"Perfil"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de asistencias del estudiante",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="student_id", type="integer"),
     *                     @OA\Property(property="fecha", type="string", format="date"),
     *                     @OA\Property(property="estado", type="string", example="presente"),
     *                     @OA\Property(property="observacion", type="string", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function attendance(Request $req)
    {
        $st = $this->meStudent();
        $rows = Attendance::where('student_id',$st->id)->orderByDesc('fecha')->limit(200)->get();
        return response()->json(['data'=>$rows]);
    }

    /**
     * @OA\Get(
     *     path="/api/me/schedule",
     *     summary="Mi horario",
     *     description="Obtiene el horario de clases del estudiante autenticado",
     *     operationId="getMySchedule",
     *     tags={"Perfil"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Horario del estudiante",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="dia", type="string", example="Lunes"),
     *                     @OA\Property(property="inicio", type="string", format="time", example="08:00:00"),
     *                     @OA\Property(property="fin", type="string", format="time", example="09:00:00"),
     *                     @OA\Property(property="materia", type="string", example="Matemáticas"),
     *                     @OA\Property(property="docente", type="string", example="Prof. Carlos Pérez")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function schedule(Request $req)
    {
        $st = $this->meStudent();
        $sectionId = DB::table('enrollments')->where('student_id',$st->id)->value('section_id');
        $rows = DB::table('assignments')
            ->join('subjects','subjects.id','=','assignments.subject_id')
            ->join('teachers','teachers.id','=','assignments.teacher_id')
            ->where('assignments.section_id',$sectionId)
            ->select('assignments.dia','assignments.inicio','assignments.fin','subjects.nombre as materia',
                     DB::raw("CONCAT(teachers.nombres,' ',teachers.apellidos) as docente"))
            ->orderBy('dia')->orderBy('inicio')->get();
        return response()->json(['data'=>$rows]);
    }
}
