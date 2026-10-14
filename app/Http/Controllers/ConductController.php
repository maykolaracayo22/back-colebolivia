<?php

namespace App\Http\Controllers;

use App\Models\ConductIncident;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class ConductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/conduct",
     *     summary="Listar registros de conducta",
     *     description="Obtiene un listado paginado de incidentes de conducta con filtros opcionales.",
     *     operationId="getConduct",
     *     tags={"Conduct"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="student_id", in="query", required=false, description="Filtrar por estudiante", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="tipo", in="query", required=false, description="Tipo de registro", @OA\Schema(type="string", enum={"positiva","negativa"})),
     *     @OA\Parameter(name="severidad", in="query", required=false, description="Severidad", @OA\Schema(type="string", enum={"leve","moderada","grave"})),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ConductIncident")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="per_page", type="integer")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', ConductIncident::class);

        $q = ConductIncident::with(['student', 'reportedBy', 'aprobadoPor'])
            ->when($request->student_id, fn($qq) => $qq->where('student_id', $request->student_id))
            ->when($request->tipo, fn($qq) => $qq->where('tipo', $request->tipo))
            ->when($request->severidad, fn($qq) => $qq->where('severidad', $request->severidad))
            ->orderBy('fecha', 'desc');

        return response()->json($q->paginate(20));
    }

    /**
     * @OA\Post(
     *     path="/api/conduct",
     *     summary="Crear registro de conducta",
     *     description="Crea un nuevo incidente de conducta para un estudiante.",
     *     operationId="createConduct",
     *     tags={"Conduct"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"student_id","tipo","descripcion","fecha","severidad"},
     *             @OA\Property(property="student_id", type="integer", example=123),
     *             @OA\Property(property="tipo", type="string", enum={"positiva","negativa"}),
     *             @OA\Property(property="descripcion", type="string"),
     *             @OA\Property(property="fecha", type="string", format="date"),
     *             @OA\Property(property="severidad", type="string", enum={"leve","moderada","grave"}),
     *             @OA\Property(property="acciones_tomadas", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Creado", @OA\JsonContent(ref="#/components/schemas/ConductIncident")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(Request $request)
    {
        $this->authorize('create', ConductIncident::class);

        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'tipo' => 'required|in:positiva,negativa',
            'descripcion' => 'required|string|max:500',
            'fecha' => 'required|date',
            'severidad' => 'required|in:leve,moderada,grave',
            'acciones_tomadas' => 'nullable|string|max:500',
        ]);

        $data['reported_by'] = $request->user()->id;

        $incident = ConductIncident::create($data);
        return response()->json($incident, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/conduct/{id}",
     *     summary="Ver registro de conducta",
     *     description="Obtiene el detalle de un incidente de conducta.",
     *     operationId="getConductById",
     *     tags={"Conduct"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del incidente", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalle", @OA\JsonContent(ref="#/components/schemas/ConductIncident")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function show(ConductIncident $conductIncident)
    {
        $this->authorize('view', $conductIncident);
        return response()->json($conductIncident->load(['student', 'reportedBy', 'aprobadoPor']));
    }

    /**
     * @OA\Put(
     *     path="/api/conduct/{id}",
     *     summary="Actualizar registro de conducta",
     *     description="Actualiza información del incidente de conducta.",
     *     operationId="updateConduct",
     *     tags={"Conduct"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del incidente", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="descripcion", type="string"),
     *             @OA\Property(property="severidad", type="string", enum={"leve","moderada","grave"}),
     *             @OA\Property(property="acciones_tomadas", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Actualizado", @OA\JsonContent(ref="#/components/schemas/ConductIncident")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="No encontrado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function update(Request $request, ConductIncident $conductIncident)
    {
        $this->authorize('update', $conductIncident);

        $data = $request->validate([
            'descripcion' => 'sometimes|string|max:500',
            'severidad' => 'sometimes|in:leve,moderada,grave',
            'acciones_tomadas' => 'nullable|string|max:500',
        ]);

        $conductIncident->update($data);
        return response()->json($conductIncident);
    }

    /**
     * @OA\Patch(
     *     path="/api/conduct/{id}/approve",
     *     summary="Aprobar incidente de conducta",
     *     description="Aprueba un incidente de conducta por un usuario autorizado.",
     *     operationId="approveConduct",
     *     tags={"Conduct"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del incidente", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Aprobado", @OA\JsonContent(ref="#/components/schemas/ConductIncident")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function approve($id)
    {
        $incident = ConductIncident::findOrFail($id);
        $this->authorize('approve', $incident);

        $incident->update([
            'aprobado_por' => request()->user()->id,
            'aprobado_at' => now(),
        ]);

        return response()->json($incident);
    }

    /**
     * @OA\Delete(
     *     path="/api/conduct/{id}",
     *     summary="Eliminar registro de conducta",
     *     description="Elimina un incidente de conducta.",
     *     operationId="deleteConduct",
     *     tags={"Conduct"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del incidente", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Eliminado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function destroy(ConductIncident $conductIncident)
    {
        $this->authorize('delete', $conductIncident);
        $conductIncident->delete();
        return response()->json(['deleted' => true]);
    }
}
