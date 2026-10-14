<?php
// app/Http/Controllers/Api/ActaEvaluationController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Acta;
use App\Models\ActaEvaluation;
use Illuminate\Http\Request;

class ActaEvaluationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/acta-evaluations",
     *     summary="Crear nueva evaluación en acta",
     *     description="Crea una nueva evaluación dentro de un acta. Solo funciona si el acta está en estado DRAFT.",
     *     operationId="storeActaEvaluation",
     *     tags={"Evaluaciones"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"acta_id","nombre","peso"},
     *             @OA\Property(property="acta_id", type="integer", example=1),
     *             @OA\Property(property="nombre", type="string", example="Examen Final"),
     *             @OA\Property(property="peso", type="number", format="float", example=50),
     *             @OA\Property(property="fecha", type="string", format="date", example="2024-11-30")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Evaluación creada exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/ActaEvaluation")
     *     ),
     *     @OA\Response(response=403, description="Acta no está en estado DRAFT"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    // POST /api/acta-evaluations
    public function store(Request $request)
    {
        $data = $request->validate([
            'acta_id' => ['required','exists:actas,id'],
            'nombre'  => ['required','string','max:255'],
            'peso'    => ['required','integer','min:0','max:100'],
            'fecha'   => ['nullable','date'],
        ]);

        $acta = Acta::findOrFail($data['acta_id']);
        $this->authorize('update', $acta);

        if ($acta->status !== 'DRAFT') {
            return response()->json(['message' => 'Solo se puede editar en DRAFT.'], 422);
        }

        $eval = ActaEvaluation::create($data);

        return response()->json($eval, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/acta-evaluations/{id}",
     *     summary="Actualizar evaluación existente",
     *     description="Actualiza los datos de una evaluación. Solo funciona si el acta está en estado DRAFT.",
     *     operationId="updateActaEvaluation",
     *     tags={"Evaluaciones"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la evaluación",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nombre", type="string", example="Examen Parcial"),
     *             @OA\Property(property="peso", type="number", format="float", example=40),
     *             @OA\Property(property="fecha", type="string", format="date", example="2024-12-15")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Evaluación actualizada exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/ActaEvaluation")
     *     ),
     *     @OA\Response(response=403, description="Acta no está en estado DRAFT"),
     *     @OA\Response(response=404, description="Evaluación no encontrada")
     * )
     */
    // PUT /api/acta-evaluations/{id}
    public function update(Request $request, $id)
    {
        $eval = ActaEvaluation::findOrFail($id);
        $acta = $eval->acta;

        $this->authorize('update', $acta);

        if ($acta->status !== 'DRAFT') {
            return response()->json(['message' => 'Solo se puede editar en DRAFT.'], 422);
        }

        $data = $request->validate([
            'nombre' => ['sometimes','string','max:255'],
            'peso'   => ['sometimes','integer','min:0','max:100'],
            'fecha'  => ['sometimes','date'],
        ]);

        $eval->update($data);

        return response()->json($eval);
    }

    /**
     * @OA\Delete(
     *     path="/api/acta-evaluations/{id}",
     *     summary="Eliminar evaluación",
     *     description="Elimina una evaluación del acta. Solo funciona si el acta está en estado DRAFT.",
     *     operationId="destroyActaEvaluation",
     *     tags={"Evaluaciones"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la evaluación a eliminar",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Evaluación eliminada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Evaluación eliminada")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Acta no está en estado DRAFT"),
     *     @OA\Response(response=404, description="Evaluación no encontrada")
     * )
     */
    // DELETE /api/acta-evaluations/{id}
    public function destroy($id)
    {
        $eval = ActaEvaluation::findOrFail($id);
        $acta = $eval->acta;

        $this->authorize('update', $acta);

        if ($acta->status !== 'DRAFT') {
            return response()->json(['message' => 'Solo se puede editar en DRAFT.'], 422);
        }

        $eval->delete();

        return response()->json(['deleted' => true], 204);
    }
}
