<?php

namespace App\Http\Controllers;

use App\Models\Period;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PeriodController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/periods",
     *     summary="Listar periodos académicos",
     *     description="Devuelve la lista de periodos académicos (T1, T2, T3) filtrados por año.",
     *     operationId="indexPeriods",
     *     tags={"Periods"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         required=false,
     *         description="Año de gestión",
     *         @OA\Schema(type="integer", example=2024)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de periodos",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Period"))
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function index(Request $req)
    {
        return Period::query()
            ->when($req->year, fn ($x) => $x->where('year', $req->year))
            ->orderBy('year', 'desc')
            ->orderBy('code')
            ->get();
    }

    /**
     * @OA\Post(
     *     path="/api/periods",
     *     summary="Crear periodo académico",
     *     description="Crea un nuevo periodo académico (T1/T2/T3) para un año específico. Si no se envía 'code', se genera automáticamente.",
     *     operationId="storePeriod",
     *     tags={"Periods"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"year"},
     *             @OA\Property(property="year", type="integer", example=2025),
     *             @OA\Property(
     *                 property="code",
     *                 type="string",
     *                 enum={"T1","T2","T3"},
     *                 nullable=true,
     *                 example="T1",
     *                 description="Opcional. Si no se envía, el sistema asigna T1/T2/T3 automáticamente según el año."
     *             ),
     *             @OA\Property(property="inicio", type="string", format="date", nullable=true, example="2025-02-01"),
     *             @OA\Property(property="fin", type="string", format="date", nullable=true, example="2025-03-31"),
     *             @OA\Property(
     *                 property="estado",
     *                 type="string",
     *                 enum={"ABIERTO","CERRADO"},
     *                 nullable=true,
     *                 example="ABIERTO"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Periodo creado", @OA\JsonContent(ref="#/components/schemas/Period")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Validación fallida o periodo duplicado")
     * )
     */
    public function store(Request $req)
    {
        $data = $req->validate([
            'year'   => ['required', 'digits:4'],
            // AHORA OPCIONAL
            'code'   => ['nullable', Rule::in(['T1', 'T2', 'T3'])],
            'inicio' => ['nullable', 'date'],
            'fin'    => ['nullable', 'date', 'after_or_equal:inicio'],
            'estado' => ['nullable', Rule::in(['ABIERTO', 'CERRADO'])],
        ]);

        // Si el cliente envía code, validamos unicidad; si no, dejamos que el modelo genere uno libre
        if (!empty($data['code'])) {
            $exists = Period::where('year', $data['year'])
                ->where('code', $data['code'])
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'Periodo duplicado (year + code)'], 422);
            }
        }

        $row = Period::create($data);

        return response()->json($row, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/periods/{id}",
     *     summary="Actualizar periodo académico",
     *     description="Actualiza las fechas y estado de un periodo académico existente.",
     *     operationId="updatePeriod",
     *     tags={"Periods"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del periodo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="inicio", type="string", format="date", nullable=true, example="2025-02-01"),
     *             @OA\Property(property="fin", type="string", format="date", nullable=true, example="2025-03-31"),
     *             @OA\Property(
     *                 property="estado",
     *                 type="string",
     *                 enum={"ABIERTO","CERRADO"},
     *                 nullable=true,
     *                 example="CERRADO"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Periodo actualizado", @OA\JsonContent(ref="#/components/schemas/Period")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Periodo no encontrado"),
     *     @OA\Response(response=422, description="Validación fallida")
     * )
     */
    public function update(Request $req, Period $period)
    {
        $data = $req->validate([
            'inicio' => ['nullable', 'date'],
            'fin'    => ['nullable', 'date', 'after_or_equal:inicio'],
            'estado' => ['nullable', Rule::in(['ABIERTO', 'CERRADO'])],
        ]);

        $period->update($data);

        return $period;
    }
}
