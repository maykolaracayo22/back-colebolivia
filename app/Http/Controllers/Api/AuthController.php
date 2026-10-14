<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Autenticación y gestión de sesiones"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Iniciar sesión",
     *     description="Autentica un usuario con username y contraseña. Retorna token JWT y datos del usuario.",
     *     operationId="login",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","password"},
     *             @OA\Property(property="username", type="string", example="jperez"),
     *             @OA\Property(property="password", type="string", example="D202401001")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="mustChangePassword", type="boolean"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Credenciales inválidas")
     * )
     */
    public function login(Request $request)
    {
        // Aceptar username, email, o identifier
        $identifier = $request->input('username') ?? $request->input('email') ?? $request->input('identifier');
        $password = $request->input('password');

        if (!$identifier || !$password) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario/email y contraseña son requeridos'
            ], 422);
        }

        // 1. Primero buscar en tabla users
        $user = User::where('username', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if ($user && Hash::check($password, $user->password)) {
            return $this->authenticateUser($user);
        }

        // 2. Si no está en users, buscar en teachers (con password_temp)
        $teacher = Teacher::where('usuario', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if ($teacher && $teacher->password_temp === $password) {
            // Crear usuario automáticamente para el docente
            $user = $this->createUserForTeacher($teacher);
            return $this->authenticateUser($user, true); // mustChangePassword = true
        }

        // 3. Si no está en teachers, buscar en students (con password_temp)
        $student = Student::where('usuario', $identifier)
            ->orWhere('email', $identifier)
            ->orWhere('codigo', $identifier)
            ->first();

        if ($student && $student->password_temp === $password) {
            // Crear usuario automáticamente para el estudiante
            $user = $this->createUserForStudent($student);
            return $this->authenticateUser($user, true); // mustChangePassword = true
        }

        return response()->json([
            'success' => false,
            'message' => 'Credenciales inválidas'
        ], 401);
    }

    /**
     * Autentica un usuario y retorna la respuesta JSON
     */
    private function authenticateUser(User $user, bool $forceChangePassword = false)
    {
        // Eliminar tokens anteriores y crear uno nuevo
        $user->tokens()->delete();

        // Obtener rol principal (Spatie) o el campo role
        $roleSpatie = $user->getRoleNames()->first();
        $role = $this->normalizeRole($roleSpatie ?? $user->role ?? 'user');

        // Obtener permisos/abilities
        $abilities = $this->getAbilitiesForRole($role, $user);

        $token = $user->createToken('auth-token', $abilities)->plainTextToken;

        // Cargar relaciones
        $user->load(['teacher', 'student']);

        return response()->json([
            'success' => true,
            'token' => $token,
            'mustChangePassword' => (bool) $user->must_change_password,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $role,
                'abilities' => $abilities,
                'teacherId' => $user->teacher_id,
                'studentId' => $user->student_id,
                'teacher' => $user->teacher ? [
                    'id' => $user->teacher->id,
                    'nombre' => $user->teacher->nombres,
                    'apellido' => $user->teacher->apellidos,
                    'especialidad' => $user->teacher->especialidad ?? null,
                ] : null,
                'student' => $user->student ? [
                    'id' => $user->student->id,
                    'nombre' => $user->student->nombres,
                    'apellido' => $user->student->apellidos,
                    'codigo' => $user->student->codigo,
                ] : null,
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/change-password",
     *     summary="Cambiar contraseña",
     *     description="Permite al usuario cambiar su contraseña. Requerido en primer inicio de sesión.",
     *     operationId="changePassword",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"password","password_confirmation"},
     *             @OA\Property(property="password", type="string", minLength=8, example="NuevaPass123"),
     *             @OA\Property(property="password_confirmation", type="string", example="NuevaPass123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Contraseña actualizada"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed'
        ]);

        $user = $request->user();
        $user->password = Hash::make($request->password);
        $user->must_change_password = false; // Ya cambió su contraseña
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada exitosamente'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/me",
     *     summary="Obtener usuario autenticado",
     *     description="Retorna los datos del usuario actualmente autenticado.",
     *     operationId="me",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Datos del usuario"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load(['teacher', 'student']);

        $roleSpatie = $user->getRoleNames()->first();
        $role = $this->normalizeRole($roleSpatie ?? $user->role ?? 'user');
        $abilities = $this->getAbilitiesForRole($role, $user);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $role,
                'abilities' => $abilities,
                'teacherId' => $user->teacher_id,
                'studentId' => $user->student_id,
                'teacher' => $user->teacher ? [
                    'id' => $user->teacher->id,
                    'nombre' => $user->teacher->nombres,
                    'apellido' => $user->teacher->apellidos,
                    'especialidad' => $user->teacher->especialidad ?? null,
                ] : null,
                'student' => $user->student ? [
                    'id' => $user->student->id,
                    'nombre' => $user->student->nombres,
                    'apellido' => $user->student->apellidos,
                    'codigo' => $user->student->codigo,
                ] : null,
            ]
        ]);
    }

    /**
     * Normaliza el nombre del rol a formato snake_case para el frontend
     */
    private function normalizeRole(?string $role): string
    {
        if (!$role) return 'user';

        $roleMap = [
            'Admin' => 'admin',
            'Director General' => 'director_general',
            'Director Académico' => 'director_academico',
            'Docente' => 'teacher',
            'Estudiante' => 'student',
            'Padre' => 'parent',
            'Secretaria' => 'secretaria',
            'Regente' => 'regente',
            'Finanzas' => 'finanzas',
            'SuperAdmin' => 'admin',
        ];

        return $roleMap[$role] ?? strtolower(str_replace(' ', '_', $role));
    }

    /**
     * Obtiene los abilities/permisos según el rol
     */
    private function getAbilitiesForRole(string $role, User $user): array
    {
        // Si tiene permisos de Spatie, usarlos
        $spatiePermissions = $user->getAllPermissions()->pluck('name')->toArray();
        if (!empty($spatiePermissions)) {
            return $spatiePermissions;
        }

        // Fallback: abilities por rol
        return match ($role) {
            'admin', 'director_general' => ['*'],
            'director_academico' => [
                'actas.ver', 'actas.crear', 'actas.editar', 'notas.consolidar',
                'reportes.ver', 'reportes.exportar', 'estudiantes.ver', 'docentes.ver'
            ],
            'teacher' => [
                'notas.ver', 'notas.crear', 'notas.editar',
                'asistencia.ver', 'asistencia.tomar', 'reportes.ver'
            ],
            'student' => ['notas.ver', 'asistencia.ver', 'reportes.ver'],
            'parent' => ['notas.ver', 'asistencia.ver', 'reportes.ver', 'conducta.ver'],
            'secretaria' => [
                'preinscripciones.ver', 'preinscripciones.crear', 'preinscripciones.aprobar',
                'matriculas.ver', 'matriculas.crear', 'reportes.exportar'
            ],
            'regente' => ['conducta.ver', 'conducta.crear', 'listas.ver'],
            'finanzas' => ['pagos.ver', 'pagos.crear', 'reportes.financieros'],
            default => ['self.ver'],
        };
    }

    /**
     * Crea un usuario automáticamente para un docente
     */
    private function createUserForTeacher(Teacher $teacher): User
    {
        // Si ya tiene user_id, retornar ese usuario
        if ($teacher->user_id) {
            return User::findOrFail($teacher->user_id);
        }

        // Crear nuevo usuario
        $user = User::create([
            'name' => $teacher->nombres . ' ' . $teacher->apellidos,
            'email' => $teacher->email ?? $teacher->usuario . '@colegio.edu.bo',
            'username' => $teacher->usuario,
            'password' => Hash::make($teacher->password_temp),
            'role' => 'teacher',  // Valor del ENUM en minúsculas
            'must_change_password' => true,
            'teacher_id' => $teacher->id,
        ]);

        // Asignar rol Spatie si existe
        if (class_exists('\Spatie\Permission\Models\Role')) {
            $role = \Spatie\Permission\Models\Role::where('name', 'Docente')->first();
            if ($role) {
                $user->assignRole($role);
            }
        }

        // Actualizar teacher con el user_id
        $teacher->update(['user_id' => $user->id]);

        return $user;
    }

    /**
     * Crea un usuario automáticamente para un estudiante
     */
    private function createUserForStudent(Student $student): User
    {
        // Si ya tiene user_id, retornar ese usuario
        if ($student->user_id) {
            return User::findOrFail($student->user_id);
        }

        // Crear nuevo usuario
        $user = User::create([
            'name' => $student->nombres . ' ' . $student->apellidos,
            'email' => $student->email ?? $student->codigo . '@estudiante.colegio.edu.bo',
            'username' => $student->usuario ?? $student->codigo,
            'password' => Hash::make($student->password_temp),
            'role' => 'student',  // Valor del ENUM en minúsculas
            'must_change_password' => true,
            'student_id' => $student->id,
        ]);

        // Asignar rol Spatie si existe
        if (class_exists('\Spatie\Permission\Models\Role')) {
            $role = \Spatie\Permission\Models\Role::where('name', 'Estudiante')->first();
            if ($role) {
                $user->assignRole($role);
            }
        }

        // Actualizar student con el user_id
        $student->update(['user_id' => $user->id]);

        return $user;
    }
}
