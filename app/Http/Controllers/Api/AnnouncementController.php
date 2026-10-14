<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /** Publicados disponibles para la página principal. */
    public function publicIndex()
    {
        $items = Announcement::query()
            ->where('is_published', true)
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhereDate('published_at', '<=', now()->toDateString());
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $items]);
    }

    /** Lista completa para administración. */
    public function index()
    {
        return response()->json([
            'data' => Announcement::query()->latest('id')->get(),
        ]);
    }

    /** Crea una noticia/comunicado publicado o en borrador. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'category' => ['required', 'string', 'max:100'],
            'summary' => ['required', 'string', 'max:2000'],
            'content' => ['required', 'string', 'max:20000'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['required', 'boolean'],
        ]);

        $data['author_id'] = $request->user()->id;
        $data['published_at'] = $data['published_at'] ?? now()->toDateString();

        $announcement = Announcement::create($data);

        return response()->json([
            'message' => 'Comunicado creado correctamente.',
            'data' => $announcement,
        ], 201);
    }
}
