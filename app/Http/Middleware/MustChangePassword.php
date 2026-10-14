<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MustChangePassword
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && $user->must_change_password && ! $request->is('api/change-password')) {
            return response()->json([
                'message' => 'Debes cambiar tu contraseÃ±a antes de continuar',
                'code'    => 'must_change_password',
            ], 403);
        }
        return $next($request);
    }
}
