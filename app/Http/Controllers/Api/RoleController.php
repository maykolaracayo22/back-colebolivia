<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use OpenApi\Annotations as OA;


/**
 * @OA\Schema(
 *     schema="Role",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="admin"),
 *     @OA\Property(property="guard_name", type="string", example="web"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Permission",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="users.manage"),
 *     @OA\Property(property="guard_name", type="string", example="web"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class RoleController extends Controller
{
    // Listar roles
    /**
     * @OA\Get(
     *     path="/api/roles",
     *     summary="Listar roles",
     *     description="Obtiene todos los roles registrados.",
     *     operationId="getRoles",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de roles",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Role"))
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function index()
    {
        return Role::all();
    }

    // Crear rol
    /**
     * @OA\Post(
     *     path="/api/roles",
     *     summary="Crear rol",
     *     description="Crea un nuevo rol por nombre.",
     *     operationId="createRole",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="coordinator")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Rol creado", @OA\JsonContent(ref="#/components/schemas/Role")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|unique:roles,name']);
        $role = Role::create(['name' => $data['name']]);
        return response()->json($role, 201);
    }

    // Editar rol
    /**
     * @OA\Put(
     *     path="/api/roles/{id}",
     *     summary="Actualizar rol",
     *     description="Actualiza el nombre de un rol.",
     *     operationId="updateRole",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del rol", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(required={"name"}, @OA\Property(property="name", type="string", example="coordinator"))
     *     ),
     *     @OA\Response(response=200, description="Rol actualizado", @OA\JsonContent(ref="#/components/schemas/Role")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Rol no encontrado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $data = $request->validate(['name' => 'required|string|unique:roles,name,' . $id]);
        $role->name = $data['name'];
        $role->save();
        return response()->json($role);
    }

    // Eliminar rol
    /**
     * @OA\Delete(
     *     path="/api/roles/{id}",
     *     summary="Eliminar rol",
     *     description="Elimina un rol existente.",
     *     operationId="deleteRole",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del rol", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Rol eliminado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Rol no encontrado")
     * )
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();
        return response()->json(['message' => 'Rol eliminado']);
    }

    // Asignar rol a usuario
    /**
     * @OA\Post(
     *     path="/api/users/{id}/roles",
     *     summary="Asignar rol a usuario",
     *     description="Asigna un rol existente a un usuario.",
     *     operationId="assignRoleToUser",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del usuario", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(required={"role"}, @OA\Property(property="role", type="string", example="teacher"))
     *     ),
     *     @OA\Response(response=200, description="Rol asignado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Usuario o rol no encontrado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function assignToUser(Request $request, $userId)
    {
        $data = $request->validate(['role' => 'required|string|exists:roles,name']);
        $user = User::findOrFail($userId);
        $user->assignRole($data['role']);
        return response()->json(['message' => 'Rol asignado']);
    }

    // Quitar rol a usuario
    /**
     * @OA\Delete(
     *     path="/api/users/{id}/roles",
     *     summary="Quitar rol a usuario",
     *     description="Remueve un rol de un usuario.",
     *     operationId="removeRoleFromUser",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del usuario", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(required={"role"}, @OA\Property(property="role", type="string", example="teacher"))
     *     ),
     *     @OA\Response(response=200, description="Rol removido"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Usuario o rol no encontrado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function removeFromUser(Request $request, $userId)
    {
        $data = $request->validate(['role' => 'required|string|exists:roles,name']);
        $user = User::findOrFail($userId);
        $user->removeRole($data['role']);
        return response()->json(['message' => 'Rol removido']);
    }
}

class PermissionController extends Controller
{
    // Listar permisos
    /**
     * @OA\Get(
     *     path="/api/permissions",
     *     summary="Listar permisos",
     *     description="Obtiene todos los permisos registrados.",
     *     operationId="getPermissions",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de permisos",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Permission"))
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function index()
    {
        return Permission::all();
    }

    // Crear permiso
    /**
     * @OA\Post(
     *     path="/api/permissions",
     *     summary="Crear permiso",
     *     description="Crea un nuevo permiso por nombre.",
     *     operationId="createPermission",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(required={"name"}, @OA\Property(property="name", type="string", example="grades.edit"))
     *     ),
     *     @OA\Response(response=201, description="Permiso creado", @OA\JsonContent(ref="#/components/schemas/Permission")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|unique:permissions,name']);
        $permission = Permission::create(['name' => $data['name']]);
        return response()->json($permission, 201);
    }

    // Asignar permiso a rol
    /**
     * @OA\Post(
     *     path="/api/roles/{id}/permissions",
     *     summary="Asignar permiso a rol",
     *     description="Asigna un permiso a un rol específico.",
     *     operationId="assignPermissionToRole",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del rol", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(required={"permission"}, @OA\Property(property="permission", type="string", example="grades.edit"))
     *     ),
     *     @OA\Response(response=200, description="Permiso asignado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Rol o permiso no encontrado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function assignToRole(Request $request, $roleId)
    {
        $data = $request->validate(['permission' => 'required|string|exists:permissions,name']);
        $role = Role::findOrFail($roleId);
        $role->givePermissionTo($data['permission']);
        return response()->json(['message' => 'Permiso asignado']);
    }

    // Quitar permiso de rol
    /**
     * @OA\Delete(
     *     path="/api/roles/{id}/permissions",
     *     summary="Quitar permiso de rol",
     *     description="Revoca un permiso de un rol específico.",
     *     operationId="removePermissionFromRole",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID del rol", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(required={"permission"}, @OA\Property(property="permission", type="string", example="grades.edit"))
     *     ),
     *     @OA\Response(response=200, description="Permiso removido"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Rol o permiso no encontrado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function removeFromRole(Request $request, $roleId)
    {
        $data = $request->validate(['permission' => 'required|string|exists:permissions,name']);
        $role = Role::findOrFail($roleId);
        $role->revokePermissionTo($data['permission']);
        return response()->json(['message' => 'Permiso removido']);
    }
}
