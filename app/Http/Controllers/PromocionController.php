<?php

namespace App\Http\Controllers;

use App\Models\Promocion;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class PromocionController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/promociones",
     *   summary="Listar promociones",
     *   description="Lista promociones con filtros opcionales por gestión, estado y estudiante.",
     *   operationId="listPromociones",
     *   tags={"Promoción"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="gestion_origen", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="gestion_destino", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="estado", in="query", @OA\Schema(type="string", enum={"pendiente","aprobado","rechazado"})),
     *   @OA\Parameter(name="student_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Listado", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Promocion")))
     * )
     */
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json([]);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *   path="/api/promociones",
     *   summary="Crear promoción",
     *   description="Registra una promoción individual para un estudiante.",
     *   operationId="createPromocion",
     *   tags={"Promoción"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"student_id","gestion_origen","gestion_destino","nivel_origen","curso_origen","paralelo_origen","nivel_destino","curso_destino","paralelo_destino"},
     *       @OA\Property(property="student_id", type="integer"),
     *       @OA\Property(property="gestion_origen", type="integer"),
     *       @OA\Property(property="gestion_destino", type="integer"),
     *       @OA\Property(property="nivel_origen", type="string"),
     *       @OA\Property(property="curso_origen", type="integer"),
     *       @OA\Property(property="paralelo_origen", type="string"),
     *       @OA\Property(property="nivel_destino", type="string"),
     *       @OA\Property(property="curso_destino", type="integer"),
     *       @OA\Property(property="paralelo_destino", type="string"),
     *       @OA\Property(property="observaciones", type="string")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Creado", @OA\JsonContent(ref="#/components/schemas/Promocion")),
     *   @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(Request $request)
    {
        return response()->json([], 201);
    }

    /**
     * @OA\Get(
     *   path="/api/promociones/{id}",
     *   summary="Ver promoción",
     *   operationId="showPromocion",
     *   tags={"Promoción"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Detalle", @OA\JsonContent(ref="#/components/schemas/Promocion")),
     *   @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function show(Promocion $promocion)
    {
        return response()->json($promocion);
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * @OA\Put(
     *   path="/api/promociones/{id}",
     *   summary="Actualizar promoción",
     *   operationId="updatePromocion",
     *   tags={"Promoción"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="nivel_destino", type="string"),
     *       @OA\Property(property="curso_destino", type="integer"),
     *       @OA\Property(property="paralelo_destino", type="string"),
     *       @OA\Property(property="observaciones", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Actualizado", @OA\JsonContent(ref="#/components/schemas/Promocion")),
     *   @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function update(Request $request, Promocion $promocion)
    {
        return response()->json($promocion);
    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * @OA\Delete(
     *   path="/api/promociones/{id}",
     *   summary="Eliminar promoción",
     *   operationId="deletePromocion",
     *   tags={"Promoción"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Eliminado"),
     *   @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function destroy(Promocion $promocion)
    {
        return response()->json(null, 204);
    }

    /**
     * @OA\Post(
     *   path="/api/promociones/{promocion}/aprobar",
     *   summary="Aprobar promoción",
     *   operationId="approvePromocion",
     *   tags={"Promoción"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="promocion", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=false, @OA\JsonContent(@OA\Property(property="observaciones", type="string"))),
     *   @OA\Response(response=200, description="Aprobada", @OA\JsonContent(ref="#/components/schemas/Promocion")),
     *   @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function aprobar(Promocion $promocion, Request $request)
    {
        return response()->json($promocion);
    }

    /**
     * @OA\Post(
     *   path="/api/promociones/{promocion}/rechazar",
     *   summary="Rechazar promoción",
     *   operationId="rejectPromocion",
     *   tags={"Promoción"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="promocion", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=false, @OA\JsonContent(@OA\Property(property="observaciones", type="string"))),
     *   @OA\Response(response=200, description="Rechazada", @OA\JsonContent(ref="#/components/schemas/Promocion")),
     *   @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function rechazar(Promocion $promocion, Request $request)
    {
        return response()->json($promocion);
    }

    /**
     * @OA\Post(
     *   path="/api/promociones/masiva",
     *   summary="Promoción masiva",
     *   description="Ejecuta promoción masiva para un conjunto de estudiantes hacia un destino.",
     *   operationId="promocionMasiva",
     *   tags={"Promoción"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"student_ids","gestion_destino","nivel_destino","curso_destino","paralelo_destino"},
     *       @OA\Property(property="student_ids", type="array", @OA\Items(type="integer")),
     *       @OA\Property(property="gestion_destino", type="integer"),
     *       @OA\Property(property="nivel_destino", type="string"),
     *       @OA\Property(property="curso_destino", type="integer"),
     *       @OA\Property(property="paralelo_destino", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Resultado", @OA\JsonContent(
     *       @OA\Property(property="processed", type="integer"),
     *       @OA\Property(property="approved", type="integer"),
     *       @OA\Property(property="rejected", type="integer")
     *   ))
     * )
     */
    public function promocionMasiva(Request $request)
    {
        return response()->json(['processed' => 0, 'approved' => 0, 'rejected' => 0]);
    }
}
