<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/subjects",
     *     summary="Listar materias",
     *     operationId="indexSubjects",
     *     description="Devuelve un listado paginado de materias con filtros por nivel y bï¿½squeda por nombre.",
     *     tags={"Subjects"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="nivel", in="query", required=false, description="Nivel (inicial, primaria, secundaria)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="q", in="query", required=false, description="Texto a buscar en el nombre", @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Listado paginado de materias",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Subject")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function index(Request $req) {
        $q = Subject::query()
            ->when($req->nivel, fn($x)=>$x->where('nivel',$req->nivel))
            ->when($req->q, fn($x)=>$x->where('nombre','like','%'.$req->q.'%'))
            ->orderBy('nombre');
        return $q->paginate(100);
    }

    /**
     * @OA\Post(
     *     path="/api/subjects",
     *     operationId="storeSubject",
     *     summary="Crear materia",
     *     tags={"Subjects"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre"},
     *             @OA\Property(property="nombre", type="string", example="Matemï¿½tica"),
     *             @OA\Property(property="nivel", type="string", enum={"inicial","primaria","secundaria"}, nullable=true, example="secundaria"),
     *             @OA\Property(property="area", type="string", nullable=true, example="Ciencias Exactas")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Materia creada", @OA\JsonContent(ref="#/components/schemas/Subject")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Validaciï¿½n fallida")
     * )
     */
    public function store(Request $req) {
        $data = $req->validate([
            'nombre' => ['required','string','max:120'],
            'nivel'  => ['nullable','in:inicial,primaria,secundaria'],
            'area'   => ['nullable','string','max:120'],
        ]);
        $row = Subject::create($data);
        return response()->json($row, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/subjects/{id}",
     *     summary="Obtener materia por ID",
     *     operationId="showSubject",
     *     tags={"Subjects"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de la materia", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Materia encontrada", @OA\JsonContent(ref="#/components/schemas/Subject")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Materia no encontrada")
     * )
     */
    public function show(Subject $subject) { return $subject; }

    /**
     * @OA\Put(
     *     path="/api/subjects/{id}",
     *     summary="Actualizar materia",
     *     operationId="updateSubject",
     *     tags={"Subjects"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de la materia", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nombre", type="string", example="Matemï¿½tica Avanzada"),
     *             @OA\Property(property="nivel", type="string", enum={"inicial","primaria","secundaria"}, nullable=true),
     *             @OA\Property(property="area", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Materia actualizada", @OA\JsonContent(ref="#/components/schemas/Subject")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Materia no encontrada"),
     *     @OA\Response(response=422, description="Validaciï¿½n fallida")
     * )
     */
    public function update(Request $req, Subject $subject) {
        $data = $req->validate([
            'nombre' => ['sometimes','string','max:120'],
            'nivel'  => ['nullable','in:inicial,primaria,secundaria'],
            'area'   => ['nullable','string','max:120'],
        ]);
        $subject->update($data);
        return $subject;
    }

    /**
     * @OA\Delete(
     *     path="/api/subjects/{id}",
     *     summary="Eliminar materia",
     *     operationId="destroySubject",
     *     tags={"Subjects"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de la materia", @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Materia eliminada"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Materia no encontrada")
     * )
     */
    public function destroy(Subject $subject) {
        $subject->delete();
        return response()->noContent();
    }
}
