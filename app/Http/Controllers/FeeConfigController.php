<?php

namespace App\Http\Controllers;

use App\Models\FeeConfig;
use OpenApi\Annotations as OA;
use Illuminate\Http\Request;

class FeeConfigController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/fee-configs",
     *     summary="Listar configuraciones de pensiones",
     *     description="Obtiene un listado paginado de configuraciones de pensiones, filtrable por año y nivel.",
     *     operationId="getFeeConfigs",
     *     tags={"Finance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="year", in="query", description="Año (YYYY)", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="nivel", in="query", description="Nivel (inicial/primaria/secundaria)", required=false, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/FeeConfig")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="per_page", type="integer")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', FeeConfig::class);

        $q = FeeConfig::query()
            ->when($request->year, fn($qq) => $qq->where('year', $request->year))
            ->when($request->nivel, fn($qq) => $qq->where('nivel', $request->nivel))
            ->orderBy('year', 'desc')
            ->orderBy('nivel')
            ->orderBy('grado');

        return response()->json($q->paginate(20));
    }

    /**
     * @OA\Post(
     *     path="/api/fee-configs",
     *     summary="Crear configuración",
     *     description="Crea una nueva configuración de pensiones.",
     *     operationId="createFeeConfig",
     *     tags={"Finance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"year","concepto","monto"},
     *             @OA\Property(property="year", type="integer", example=2025),
     *             @OA\Property(property="nivel", type="string", enum={"inicial","primaria","secundaria"}),
     *             @OA\Property(property="grado", type="integer", example=6),
     *             @OA\Property(property="concepto", type="string", example="Pensión mensual"),
     *             @OA\Property(property="monto", type="number", format="float", example=150.50),
     *             @OA\Property(property="fecha_vencimiento", type="string", format="date"),
     *             @OA\Property(property="obligatorio", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Configuración creada", @OA\JsonContent(ref="#/components/schemas/FeeConfig")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(Request $request)
    {
        $this->authorize('create', FeeConfig::class);

        $data = $request->validate([
            'year' => 'required|integer',
            'nivel' => 'nullable|in:inicial,primaria,secundaria',
            'grado' => 'nullable|integer|min:1|max:12',
            'concepto' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0',
            'fecha_vencimiento' => 'nullable|date',
            'obligatorio' => 'boolean',
        ]);

        $config = FeeConfig::create($data);
        return response()->json($config, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/fee-configs/{id}",
     *     summary="Ver configuración",
     *     description="Obtiene el detalle de una configuración específica.",
     *     operationId="getFeeConfig",
     *     tags={"Finance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de la configuración", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalle", @OA\JsonContent(ref="#/components/schemas/FeeConfig")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function show(FeeConfig $feeConfig)
    {
        $this->authorize('view', $feeConfig);
        return response()->json($feeConfig);
    }

    /**
     * @OA\Put(
     *     path="/api/fee-configs/{id}",
     *     summary="Actualizar configuración",
     *     description="Actualiza campos de la configuración.",
     *     operationId="updateFeeConfig",
     *     tags={"Finance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de la configuración", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="concepto", type="string"),
     *             @OA\Property(property="monto", type="number", format="float"),
     *             @OA\Property(property="fecha_vencimiento", type="string", format="date"),
     *             @OA\Property(property="obligatorio", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Actualizado", @OA\JsonContent(ref="#/components/schemas/FeeConfig")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="No encontrado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function update(Request $request, FeeConfig $feeConfig)
    {
        $this->authorize('update', $feeConfig);

        $data = $request->validate([
            'concepto' => 'sometimes|string|max:255',
            'monto' => 'sometimes|numeric|min:0',
            'fecha_vencimiento' => 'nullable|date',
            'obligatorio' => 'boolean',
        ]);

        $feeConfig->update($data);
        return response()->json($feeConfig);
    }

    /**
     * @OA\Delete(
     *     path="/api/fee-configs/{id}",
     *     summary="Eliminar configuración",
     *     description="Elimina una configuración de pensiones.",
     *     operationId="deleteFeeConfig",
     *     tags={"Finance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de la configuración", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Eliminado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function destroy(FeeConfig $feeConfig)
    {
        $this->authorize('delete', $feeConfig);
        $feeConfig->delete();
        return response()->json(['deleted' => true]);
    }
}
