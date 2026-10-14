<?php

namespace App\Http\Controllers;

use App\Models\WhatsappLog;
use App\Services\WhatsappService;
use Illuminate\Http\Request;

class WhatsappController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsappService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    public function sendMessage(Request $request)
    {
        $this->authorize('create', WhatsappLog::class);

        $data = $request->validate([
            'telefono' => 'required|string|max:20',
            'mensaje' => 'required|string|max:1000',
            'tipo' => 'required|in:recordatorio,alerta,informe',
        ]);

        $data['user_id'] = $request->user()->id;

        $result = $this->whatsappService->sendMessage($data['telefono'], $data['mensaje']);

        $log = WhatsappLog::create(array_merge($data, [
            'estado' => $result['success'] ? 'enviado' : 'fallido',
            'respuesta_api' => $result['response'] ?? null,
        ]));

        return response()->json($log, $result['success'] ? 200 : 422);
    }

    public function sendBulk(Request $request)
    {
        $this->authorize('create', WhatsappLog::class);

        $data = $request->validate([
            'mensajes' => 'required|array|max:50',
            'mensajes.*.telefono' => 'required|string|max:20',
            'mensajes.*.mensaje' => 'required|string|max:1000',
            'tipo' => 'required|in:recordatorio,alerta,informe',
        ]);

        $logs = [];
        foreach ($data['mensajes'] as $msg) {
            $result = $this->whatsappService->sendMessage($msg['telefono'], $msg['mensaje']);

            $logs[] = WhatsappLog::create([
                'user_id' => $request->user()->id,
                'telefono' => $msg['telefono'],
                'mensaje' => $msg['mensaje'],
                'tipo' => $data['tipo'],
                'estado' => $result['success'] ? 'enviado' : 'fallido',
                'respuesta_api' => $result['response'] ?? null,
            ]);
        }

        return response()->json($logs);
    }

    public function logs(Request $request)
    {
        $this->authorize('viewAny', WhatsappLog::class);

        $q = WhatsappLog::with('user')
            ->when($request->user_id, fn($qq) => $qq->where('user_id', $request->user_id))
            ->when($request->tipo, fn($qq) => $qq->where('tipo', $request->tipo))
            ->when($request->estado, fn($qq) => $qq->where('estado', $request->estado))
            ->orderBy('created_at', 'desc');

        return response()->json($q->paginate(20));
    }
}
