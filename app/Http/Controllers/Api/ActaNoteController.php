<?php
// app/Http/Controllers/Api/ActaNoteController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Acta;
use App\Models\ActaNote;
use App\Models\ActaEvaluation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActaNoteController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/acta-notes",
     *     summary="Crear o actualizar nota de estudiante",
     *     description="Registra o actualiza la nota de un estudiante en una evaluaciÃ³n especÃ­fica. Soporta notas numÃ©ricas y cualitativas. Registra historial de cambios con justificaciÃ³n.",
     *     operationId="storeActaNote",
     *     tags={"Notas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"acta_id","evaluation_id","student_id","tipo"},
     *             @OA\Property(property="acta_id", type="integer", example=1),
     *             @OA\Property(property="evaluation_id", type="integer", example=5),
     *             @OA\Property(property="student_id", type="integer", example=123),
     *             @OA\Property(property="tipo", type="string", enum={"NUM","QUAL"}, example="NUM"),
     *             @OA\Property(property="valor", type="number", format="float", example=85.5, description="Requerido si tipo=NUM"),
     *             @OA\Property(property="valor_qual", type="string", example="Aprobado", description="Requerido si tipo=QUAL"),
     *             @OA\Property(property="justificacion", type="string", example="CorrecciÃ³n de examen")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Nota registrada o actualizada exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/ActaNote")
     *     ),
     *     @OA\Response(response=422, description="Error de validaciÃ³n")
     * )
     */

    // POST /api/acta-notes  (upsert)
    public function store(Request $request)
    {
        $data = $request->validate([
            'acta_id'       => ['required','exists:actas,id'],
            'student_id'    => ['required','exists:students,id'],
            'evaluation_id' => ['nullable','exists:acta_evaluations,id'],
            'tipo'          => ['required', Rule::in(['NUM','QUAL'])],
            'valor'         => ['nullable','numeric','min:0','max:1000'],
            'valor_qual'    => ['nullable','string','max:50'],
            'justification' => ['nullable','string','max:255'], // Para cambios
        ]);

        $acta = Acta::findOrFail($data['acta_id']);
        $this->authorize('update', $acta);

        // Validar evaluaciÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³n pertenezca a la acta
        if (!empty($data['evaluation_id'])) {
            $eval = ActaEvaluation::findOrFail($data['evaluation_id']);
            if ($eval->acta_id !== $acta->id) {
                return response()->json(['message'=>'La evaluacion no pertenece a la acta.'], 422);
            }
        }

        // Buscar nota existente
        $existingNote = ActaNote::where('acta_id', $data['acta_id'])
            ->where('student_id', $data['student_id'])
            ->where('evaluation_id', $data['evaluation_id'])
            ->first();

        // Autorizar upsert (incluye ventana de ediciÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³n)
        //$this->authorize('upsert', $existingNote);

        // Registrar cambio si hay nota previa
        if ($existingNote) {
            NotaChange::create([
                'nota_id'       => $existingNote->id,
                'user_id'       => $request->user()->id,
                'old_valor'     => $existingNote->tipo === 'NUM' ? $existingNote->valor : $existingNote->valor_qual,
                'new_valor'     => $data['tipo'] === 'NUM' ? $data['valor'] : $data['valor_qual'],
                'tipo'          => $data['tipo'],
                'justification' => $data['justification'] ?? null,
                'changed_at'    => now(),
            ]);
        }

        // Upsert
        $note = ActaNote::updateOrCreate(
                [
                    'acta_id'       => $data['acta_id'],
                    'student_id'    => $data['student_id'],
                    'evaluation_id' => $data['evaluation_id'] ?? null,
                ],
                [
                    'tipo'       => $data['tipo'],
                    'valor'      => $data['tipo'] === 'NUM'  ? $data['valor']      : null,
                    'valor_qual' => $data['tipo'] === 'QUAL' ? $data['valor_qual'] : null,
                ]
            );

        return response()->json($note, $existingNote ? 200 : 201);
    }


    /**
     * @OA\Delete(
     *     path="/api/acta-notes/{id}",
     *     summary="Eliminar nota de estudiante",
     *     description="Elimina una nota registrada de un estudiante en una evaluación específica.",
     *     operationId="destroyActaNote",
     *     tags={"Notas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la nota a eliminar",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Nota eliminada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Nota eliminada")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Nota no encontrada")
     * )
     */
// DELETE /api/acta-notes/{id}
    public function destroy($id)
    {
        $note = ActaNote::findOrFail($id);
        $this->authorize('delete', $note);

        $note->delete();
        return response()->json(['deleted' => true], 204);
    }
}
