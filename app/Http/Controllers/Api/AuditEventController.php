<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuditEvent;

class AuditEventController extends Controller
{
    /**
     * Listar eventos de auditoría con filtros.
     *
     * Filtros soportados:
     * - user_id
     * - accion
     * - target
     * - from (fecha mínima ts)
     * - to   (fecha máxima ts)
     * - q    (búsqueda en accion, target, ref, user_nombre)
     */
    public function index(Request $request)
    {
        $query = AuditEvent::query();

        // Filtrar por usuario
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtrar por acción exacta
        if ($request->filled('accion')) {
            $query->where('accion', $request->accion);
        }

        // Filtrar por target (tabla / módulo)
        if ($request->filled('target')) {
            $query->where('target', $request->target);
        }

        // Rango de fechas (ts)
        if ($request->filled('from')) {
            $query->where('ts', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('ts', '<=', $request->to);
        }

        // Búsqueda libre
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('accion', 'like', "%{$q}%")
                    ->orWhere('target', 'like', "%{$q}%")
                    ->orWhere('ref', 'like', "%{$q}%")
                    ->orWhere('user_nombre', 'like', "%{$q}%");
            });
        }

        $events = $query
            ->orderByDesc('ts')
            ->paginate(50);

        return response()->json($events);
    }

    /**
     * Crear un evento de auditoría manualmente vía API.
     *
     * NOTA:
     * - user_id y user_nombre ya NO los manda el cliente
     *   se rellenan automáticamente desde el usuario autenticado.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'accion' => 'required|string|max:255',
            'target' => 'nullable|string|max:255',
            'ref'    => 'nullable|string|max:255',
            'before' => 'nullable',
            'after'  => 'nullable',
        ]);

        // Si before/after vienen como JSON string, los convertimos a array
        if (isset($data['before']) && is_string($data['before'])) {
            $decoded = json_decode($data['before'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['before'] = $decoded;
            }
        }
        if (isset($data['after']) && is_string($data['after'])) {
            $decoded = json_decode($data['after'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['after'] = $decoded;
            }
        }

        // Usuario autenticado (si existe)
        if ($request->user()) {
            $data['user_id']     = $request->user()->id;
            $data['user_nombre'] = $request->user()->name ?? $request->user()->email;
        }

        // Datos técnicos
        $data['ip'] = $request->ip();
        $data['ua'] = $request->userAgent();
        $data['ts'] = now();

        $event = AuditEvent::create($data);

        return response()->json($event, 201);
    }
}
