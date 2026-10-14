<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;

/**
 * Middleware personalizado de permisos que permite bypass para admin/superadmin
 */
class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'No autenticado'
            ], 401);
        }

        // Los parámetros vienen como ["perm1|perm2"]
        $permissions = collect($permissions)
            ->flatMap(fn($p) => explode('|', $p))
            ->map(fn($p) => strtolower(trim($p)))
            ->filter()
            ->values()
            ->all();

        $userRole = strtolower($user->role ?? '');

        // 🔓 Si es SuperAdmin o Admin pasa SIEMPRE (acceso total)
        if (in_array($userRole, ['superadmin', 'admin'])) {
            return $next($request);
        }

        // 🔓 Si tiene rol Spatie SuperAdmin o Admin
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole('SuperAdmin') || $user->hasRole('Admin')) {
                return $next($request);
            }
        }

        // 📌 Verificar permisos con Spatie
        if (method_exists($user, 'hasAnyPermission')) {
            // Spatie es case-sensitive, intentar con nombres originales
            $originalPermissions = collect($permissions)
                ->flatMap(fn($p) => explode('|', $p))
                ->map(fn($p) => trim($p))
                ->filter()
                ->values()
                ->all();

            if ($user->hasAnyPermission($originalPermissions)) {
                return $next($request);
            }
        }

        // ❌ Sin permisos
        return response()->json([
            'message' => 'No autorizado - Permiso requerido'
        ], 403);
    }
}
