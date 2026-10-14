<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Middleware de verificación de roles.
     *
     * Ejemplo en rutas:
     *   ->middleware('role:SuperAdmin|Director General')
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'No autenticado'
            ], 401);
        }

        // Los parámetros vienen como ["SuperAdmin|Director General"]
        // Se convierten en ["SuperAdmin", "Director General"]
        // Convertir a minúsculas para comparación case-insensitive
        $roles = collect($roles)
            ->flatMap(fn($r) => explode('|', $r))
            ->map(fn($r) => strtolower(trim($r)))
            ->filter()
            ->values()
            ->all();

        $userRole = strtolower($user->role ?? '');

        // 🔓 Si es SuperAdmin o Admin pasa SIEMPRE (acceso total)
        if (
            in_array($userRole, ['superadmin', 'admin'])
            || (method_exists($user, 'hasRole') && ($user->hasRole('SuperAdmin') || $user->hasRole('Admin')))
        ) {
            return $next($request);
        }

        // 📌 Validar campo `role` de users (nativo del sistema) - case insensitive
        if (!empty($userRole) && in_array($userRole, $roles, true)) {
            return $next($request);
        }

        // 📌 Validar roles Spatie (case insensitive)
        if (method_exists($user, 'getRoleNames')) {
            $userSpatieRoles = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
            if (!empty(array_intersect($userSpatieRoles, $roles))) {
                return $next($request);
            }
        }

        // ❌ Si no tiene ninguno de los roles autorizados
        return response()->json([
            'message' => 'No autorizado'
        ], 403);
    }
}
