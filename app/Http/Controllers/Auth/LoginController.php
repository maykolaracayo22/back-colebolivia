<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    public function login(Request $req)
    {
        $data = $req->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
            'device'   => ['sometimes','string'],
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas'
            ], 401);
        }

        $role = $this->normalizeRole($user->getRoleNames()->first() ?? $user->role ?? 'user');

        // abilities por rol
        $abilities = match ($role) {
            'director_general'   => ['*'],
            'director_academico' => ['grades:read','grades:centralize','plans:approve','notices:write','reports:export'],
            'secretaria'         => ['prereg:write','rude:upload','rude:centralize','parent-msg:send','reports:export'],
            'regente'            => ['conduct:write','lists:read','templates:read'],
            'finanzas'           => ['fees:write','fees:read','reports:export'],
            'admin'              => ['*'],
            'teacher'            => ['grade:write','grade:read','plans:write','reports:read'],
            'student'            => ['self:read','reports:read'],
            'parent'             => ['child:read','reports:read'],
            default              => ['self:read'],
        };

        $token = $user->createToken($data['device'] ?? 'api', $abilities)->plainTextToken;

        // Cargar relaciones
        $user->load(['teacher', 'student']);

        return response()->json([
            'success' => true,
            'token' => $token,
            'mustChangePassword' => (bool) ($user->must_change_password ?? false),
            'user'  => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'username'  => $user->username ?? $user->email,
                'role'      => $role,
                'abilities' => $abilities,
                'teacherId' => $user->teacher?->id,
                'studentId' => $user->student?->id,
                'teacher'   => $user->teacher ? [
                    'id' => $user->teacher->id,
                    'nombre' => $user->teacher->nombres,
                    'apellido' => $user->teacher->apellidos,
                ] : null,
                'student'   => $user->student ? [
                    'id' => $user->student->id,
                    'nombre' => $user->student->nombres,
                    'apellido' => $user->student->apellidos,
                    'codigo' => $user->student->codigo,
                ] : null,
            ],
        ], 200);
    }

    /**
     * Normaliza el nombre del rol a formato snake_case para el frontend
     */
    private function normalizeRole(?string $role): string
    {
        if (!$role) return 'user';

        $roleMap = [
            'Admin' => 'admin',
            'SuperAdmin' => 'admin',
            'Director General' => 'director_general',
            'Director Académico' => 'director_academico',
            'Docente' => 'teacher',
            'Estudiante' => 'student',
            'Padre' => 'parent',
            'Secretaria' => 'secretaria',
            'Regente' => 'regente',
            'Finanzas' => 'finanzas',
        ];

        return $roleMap[$role] ?? strtolower(str_replace(' ', '_', $role));
    }

    public function me(Request $req)
    {
        $u = $req->user();
        return response()->json([
            'id'        => $u->id,
            'name'      => $u->name,
            'email'     => $u->email,
            'roles'     => $u->getRoleNames(),
            'abilities' => $u->currentAccessToken()?->abilities ?? [],
        ]);
    }

    public function logout(Request $req)
    {
        $req->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'OK'], 204);
    }
}
