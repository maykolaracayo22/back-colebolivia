<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EnrollmentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/enrollments",
     *     summary="Listar matrículas",
     *     description="Obtiene un listado paginado de matrículas con filtros opcionales por año, sección y estado.",
     *     operationId="getEnrollments",
     *     tags={"Enrollments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="year", in="query", description="Filtrar por año (YYYY)", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="section_id", in="query", description="Filtrar por ID de sección", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="estado", in="query", description="Filtrar por estado de matrícula", required=false, @OA\Schema(type="string", enum={"PREINSCRITO","INSCRITO","BAJA_TEMP","BAJA_DEF"})),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de matrículas paginada",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Enrollment")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="per_page", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function index(Request $req) {
        $q = Enrollment::with(['student','section'])
            ->when($req->year, fn($x)=>$x->where('year',$req->year))
            ->when($req->section_id, fn($x)=>$x->where('section_id',$req->section_id))
            ->when($req->estado, fn($x)=>$x->where('estado',$req->estado))
            ->orderBy('year','desc')->latest();
        return $q->paginate(50);
    }

    /**
     * @OA\Post(
     *     path="/api/enrollments",
     *     summary="Crear nueva matrícula",
     *     description="Registra una nueva matrícula para un estudiante en una sección dada, validando unicidad por año y capacidad de la sección.",
     *     operationId="createEnrollment",
     *     tags={"Enrollments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"year","student_id","section_id"},
     *             @OA\Property(property="year", type="string", example="2025"),
     *             @OA\Property(property="student_id", type="integer", example=123),
     *             @OA\Property(property="section_id", type="integer", example=45),
     *             @OA\Property(property="estado", type="string", enum={"PREINSCRITO","INSCRITO","BAJA_TEMP","BAJA_DEF"}, example="INSCRITO"),
     *             @OA\Property(property="fecha", type="string", format="date", example="2025-02-10")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Matrícula creada", @OA\JsonContent(ref="#/components/schemas/Enrollment")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=422, description="Error de validación o reglas de negocio")
     * )
     */
    public function store(Request $req) {
        $data = $req->validate([
            'year'       => ['required','digits:4'],
            'student_id' => ['required','exists:students,id'],
            'section_id' => ['required','exists:sections,id'],
            'estado'     => ['nullable', Rule::in(['PREINSCRITO','INSCRITO','BAJA_TEMP','BAJA_DEF'])],
            'fecha'      => ['nullable','date'],
        ]);

        // regla: Ãºnico por aÃ±o
        $dup = Enrollment::where('year',$data['year'])
            ->where('student_id',$data['student_id'])->exists();
        if ($dup) return response()->json(['message'=>'El estudiante ya estÃ¡ matriculado en ese aÃ±o'], 422);

        // validaciÃ³n de capacidad si se definiÃ³
        $section = Section::findOrFail($data['section_id']);
        if (!empty($section->capacidad)) {
            $ocupados = Enrollment::where('year',$data['year'])
                ->where('section_id',$section->id)
                ->whereIn('estado',['PREINSCRITO','INSCRITO'])
                ->count();
            if ($ocupados >= $section->capacidad) {
                return response()->json(['message'=>'SecciÃ³n sin cupos disponibles'], 422);
            }
        }

        $row = Enrollment::create($data + ['estado' => $data['estado'] ?? 'INSCRITO']);
        return response()->json($row->load(['student','section']), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/enrollments/{id}",
     *     summary="Ver detalle de matrícula",
     *     description="Obtiene los detalles de una matrícula específica, incluyendo estudiante y sección.",
     *     operationId="getEnrollment",
     *     tags={"Enrollments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de la matrícula", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalle de matrícula", @OA\JsonContent(ref="#/components/schemas/Enrollment")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Matrícula no encontrada")
     * )
     */
    public function show(Enrollment $enrollment) { return $enrollment->load(['student','section']); }

    /**
     * @OA\Put(
     *     path="/api/enrollments/{id}",
     *     summary="Actualizar matrícula",
     *     description="Actualiza los datos de una matrícula existente. Valida capacidad de sección cuando aplica.",
     *     operationId="updateEnrollment",
     *     tags={"Enrollments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de la matrícula", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="section_id", type="integer"),
     *             @OA\Property(property="estado", type="string", enum={"PREINSCRITO","INSCRITO","BAJA_TEMP","BAJA_DEF"}),
     *             @OA\Property(property="fecha", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Matrícula actualizada", @OA\JsonContent(ref="#/components/schemas/Enrollment")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Matrícula no encontrada"),
     *     @OA\Response(response=422, description="Error de validación o reglas de negocio")
     * )
     */
    public function update(Request $req, Enrollment $enrollment) {
        $data = $req->validate([
            'section_id' => ['sometimes','exists:sections,id'],
            'estado'     => ['sometimes', Rule::in(['PREINSCRITO','INSCRITO','BAJA_TEMP','BAJA_DEF'])],
            'fecha'      => ['nullable','date'],
        ]);

        if (isset($data['section_id'])) {
            $section = Section::findOrFail($data['section_id']);
            if (!empty($section->capacidad)) {
                $ocupados = Enrollment::where('year',$enrollment->year)
                    ->where('section_id',$section->id)
                    ->whereIn('estado',['PREINSCRITO','INSCRITO'])
                    ->where('id','<>',$enrollment->id)
                    ->count();
                if ($ocupados >= $section->capacidad) {
                    return response()->json(['message'=>'SecciÃ³n sin cupos disponibles'], 422);
                }
            }
        }

        $enrollment->update($data);
        return $enrollment->load(['student','section']);
    }

    /**
     * @OA\Delete(
     *     path="/api/enrollments/{id}",
     *     summary="Eliminar matrícula",
     *     description="Elimina una matrícula del sistema.",
     *     operationId="deleteEnrollment",
     *     tags={"Enrollments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de la matrícula", @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Matrícula eliminada"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Matrícula no encontrada")
     * )
     */
    public function destroy(Enrollment $enrollment) {
        $enrollment->delete();
        return response()->noContent();
    }
}
