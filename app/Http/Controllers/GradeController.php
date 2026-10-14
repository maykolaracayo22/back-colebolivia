<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Period;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GradeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/grades",
     *     summary="Listar calificaciones",
     *     description="Obtiene lista paginada de calificaciones con filtros por año, período, estudiante y materia.",
     *     operationId="indexGrades",
     *     tags={"Grades"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="year", in="query", required=false, @OA\Schema(type="integer", example=2024)),
     *     @OA\Parameter(name="period_code", in="query", required=false, @OA\Schema(type="string", enum={"T1","T2","T3"})),
     *     @OA\Parameter(name="student_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="subject_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de calificaciones",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Grade"))
     *         )
     *     )
     * )
     */
    public function index(Request $req) {
        $q = Grade::with(['student','subject'])
            ->when($req->year, fn($x)=>$x->where('year',$req->year))
            ->when($req->period_code, fn($x)=>$x->where('period_code',$req->period_code))
            ->when($req->student_id, fn($x)=>$x->where('student_id',$req->student_id))
            ->when($req->subject_id, fn($x)=>$x->where('subject_id',$req->subject_id))
            ->orderBy('student_id');
        return $q->paginate(100);
    }

    /**
     * @OA\Post(
     *     path="/api/grades",
     *     summary="Registrar calificación",
     *     description="Registra o actualiza una calificación de estudiante en un período específico (upsert). Solo funciona si el período está ABIERTO.",
     *     operationId="storeGrade",
     *     tags={"Grades"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"year","student_id","subject_id","period_code","puntaje"},
     *             @OA\Property(property="year", type="integer", example=2024),
     *             @OA\Property(property="student_id", type="integer", example=123),
     *             @OA\Property(property="subject_id", type="integer", example=10),
     *             @OA\Property(property="period_code", type="string", enum={"T1","T2","T3"}, example="T1"),
     *             @OA\Property(property="puntaje", type="number", format="float", example=85.5, description="Calificación de 0 a 100"),
     *             @OA\Property(property="peso", type="integer", example=100, description="Peso de la evaluación (1-100)")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Calificación actualizada"),
     *     @OA\Response(response=201, description="Calificación creada"),
     *     @OA\Response(response=422, description="Período cerrado o datos inválidos")
     * )
     */
    public function store(Request $req) {
        $data = $req->validate([
            'year'        => ['required','digits:4'],
            'student_id'  => ['required','exists:students,id'],
            'subject_id'  => ['required','exists:subjects,id'],
            'period_code' => ['required', Rule::in(['T1','T2','T3'])],
            'puntaje'     => ['required','numeric','min:0','max:100'],
            'peso'        => ['nullable','integer','min:1','max:100'],
        ]);

        // periodo abierto
        $period = Period::where('year',$data['year'])->where('code',$data['period_code'])->first();
        if (!$period || $period->estado !== 'ABIERTO') {
            return response()->json(['message'=>'Período cerrado o inexistente'], 422);
        }

        $row = Grade::updateOrCreate(
            [
                'year'=>$data['year'],
                'student_id'=>$data['student_id'],
                'subject_id'=>$data['subject_id'],
                'period_code'=>$data['period_code'],
            ],
            [
                'puntaje'=>$data['puntaje'],
                'peso'=>$data['peso'] ?? 100,
                'publicado'=>false,
            ]
        );

        return response()->json($row->load(['student','subject']), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/grades/{id}",
     *     summary="Actualizar calificación",
     *     description="Actualiza una calificación existente. Solo funciona si el período está ABIERTO.",
     *     operationId="updateGrade",
     *     tags={"Grades"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="puntaje", type="number", format="float", example=90.0),
     *             @OA\Property(property="peso", type="integer", example=100),
     *             @OA\Property(property="publicado", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Calificación actualizada exitosamente"),
     *     @OA\Response(response=422, description="Período cerrado")
     * )
     */
    public function update(Request $req, Grade $grade) {
        $data = $req->validate([
            'puntaje' => ['sometimes','numeric','min:0','max:100'],
            'peso'    => ['nullable','integer','min:1','max:100'],
            'publicado'=>['boolean'],
        ]);

        // no permitir editar si publicado (ajústalo a tu flujo)
        if ($grade->publicado && isset($data['puntaje'])) {
            return response()->json(['message'=>'Nota publicada, no editable'], 422);
        }

        $grade->update($data);
        return $grade->load(['student','subject']);
    }
}
