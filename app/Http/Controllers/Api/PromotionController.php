<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PromotionService;
use OpenApi\Annotations as OA;

class PromotionController extends Controller
{
    /**
     * @OA\Post(
     *   path="/api/promotions/run",
     *   summary="Ejecutar proceso de promoción anual",
     *   description="Corre el servicio de promoción por gestión y fase. Permite modo dry-run.",
     *   operationId="runPromotionService",
     *   tags={"Promoción"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=false,
     *     @OA\JsonContent(
     *       @OA\Property(property="year", type="integer", example=2025),
     *       @OA\Property(property="phase", type="string", enum={"noviembre","diciembre"}, example="noviembre"),
     *       @OA\Property(property="dry", type="boolean", example=true)
     *     )
     *   ),
     *   @OA\Response(response=200, description="Resultado del proceso", @OA\JsonContent(
     *     @OA\Property(property="processed", type="integer"),
     *     @OA\Property(property="approved", type="integer"),
     *     @OA\Property(property="repeated", type="integer"),
     *     @OA\Property(property="errors", type="integer")
     *   ))
     * )
     */
    public function run(Request $req, PromotionService $svc)
    {
        // âœ… Ya estÃ¡ protegido por middleware de rol en la ruta

        $data = $req->validate([
            'year'  => ['nullable','integer'],
            'phase' => ['nullable','in:noviembre,diciembre'],
            'dry'   => ['nullable','boolean'],
        ]);

        $year  = $data['year']  ?? (int)date('Y');
        $phase = $data['phase'] ?? 'noviembre';
        $dry   = (bool)($data['dry'] ?? false);

        $res = $svc->runForYear($year, $phase, $dry);
        return response()->json($res);
    }
}
