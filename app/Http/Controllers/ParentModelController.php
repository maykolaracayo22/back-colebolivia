<?php

namespace App\Http\Controllers;

use App\Models\ParentModel;
use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Controlador para gestión de Padres de Familia
 */
class ParentModelController extends Controller
{
    /**
     * Listar todos los padres con filtros
     */
    public function index(Request $request)
    {
        $query = ParentModel::with(['user', 'students'])
            ->when($request->search, function($q, $search) {
                $q->whereHas('user', function($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhere('ci', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
            })
            ->when($request->student_id, function($q, $studentId) {
                $q->whereHas('students', fn($sq) => $sq->where('students.id', $studentId));
            });

        $query->latest();

        if ($request->has('per_page')) {
            return $query->paginate($request->per_page);
        }

        return response()->json(['data' => $query->get()]);
    }

    /**
     * Crear nuevo padre/tutor
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'ci' => ['required', 'string', 'max:20', 'unique:parents,ci'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'ocupacion' => ['nullable', 'string', 'max:100'],
            'lugar_trabajo' => ['nullable', 'string', 'max:255'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['exists:students,id'],
            'relationship' => ['nullable', 'string', Rule::in(['padre', 'madre', 'tutor', 'abuelo', 'otro'])],
        ]);

        return DB::transaction(function() use ($data) {
            // La tabla users requiere username y debe ser único aunque el correo ya lo sea.
            $ciDigits = preg_replace('/\D+/', '', $data['ci']);
            $usernameBase = 'padre' . ($ciDigits !== '' ? $ciDigits : substr(sha1($data['email']), 0, 12));
            $username = $usernameBase;
            $suffix = 1;
            while (User::where('username', $username)->exists()) {
                $username = $usernameBase . $suffix++;
            }

            // Crear usuario para el padre
            $user = User::create([
                'name' => $data['nombre'] . ' ' . $data['apellidos'],
                'email' => $data['email'],
                'username' => $username,
                'password' => Hash::make($data['ci']), // CI como contraseña inicial
                'role' => 'parent',
                'must_change_password' => true,
            ]);

            // Crear registro de padre
            $parent = ParentModel::create([
                'user_id' => $user->id,
                'ci' => $data['ci'],
                'phone' => $data['phone'],
                'address' => $data['address'] ?? null,
                'ocupacion' => $data['ocupacion'] ?? null,
                'lugar_trabajo' => $data['lugar_trabajo'] ?? null,
            ]);

            // Vincular estudiantes si se proporcionan
            if (!empty($data['student_ids'])) {
                $relationship = $data['relationship'] ?? 'tutor';
                foreach ($data['student_ids'] as $studentId) {
                    $parent->students()->attach($studentId, ['relationship' => $relationship]);
                }
            }

            $parent->load(['user', 'students']);

            return response()->json([
                ...$parent->toArray(),
                'password_temp' => $data['ci'],
            ], 201);
        });
    }

    /**
     * Ver detalle de un padre
     */
    public function show($id)
    {
        $parent = ParentModel::with(['user', 'students.section'])
            ->findOrFail($id);

        return response()->json($parent);
    }

    /**
     * Actualizar padre
     */
    public function update(Request $request, $id)
    {
        $parent = ParentModel::findOrFail($id);

        $data = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:100'],
            'apellidos' => ['sometimes', 'string', 'max:100'],
            'ci' => ['sometimes', 'string', 'max:20', Rule::unique('parents')->ignore($parent->id)],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($parent->user_id)],
            'phone' => ['sometimes', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'ocupacion' => ['nullable', 'string', 'max:100'],
            'lugar_trabajo' => ['nullable', 'string', 'max:255'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['exists:students,id'],
            'relationship' => ['nullable', 'string', Rule::in(['padre', 'madre', 'tutor', 'abuelo', 'otro'])],
        ]);

        return DB::transaction(function() use ($parent, $data) {
            // Actualizar usuario
            if (isset($data['nombre']) || isset($data['apellidos']) || isset($data['email'])) {
                $parent->user->update([
                    'name' => ($data['nombre'] ?? $parent->user->name) . ' ' . ($data['apellidos'] ?? ''),
                    'email' => $data['email'] ?? $parent->user->email,
                ]);
            }

            // Actualizar padre
            $parent->update(array_filter([
                'ci' => $data['ci'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'ocupacion' => $data['ocupacion'] ?? null,
                'lugar_trabajo' => $data['lugar_trabajo'] ?? null,
            ]));

            // Actualizar estudiantes vinculados
            if (isset($data['student_ids'])) {
                $relationship = $data['relationship'] ?? 'tutor';
                $syncData = [];
                foreach ($data['student_ids'] as $studentId) {
                    $syncData[$studentId] = ['relationship' => $relationship];
                }
                $parent->students()->sync($syncData);
            }

            return response()->json($parent->fresh()->load(['user', 'students']));
        });
    }

    /**
     * Eliminar padre
     */
    public function destroy($id)
    {
        $parent = ParentModel::findOrFail($id);

        return DB::transaction(function() use ($parent) {
            // Desvincular estudiantes
            $parent->students()->detach();

            // Eliminar padre y usuario
            $userId = $parent->user_id;
            $parent->delete();
            User::destroy($userId);

            return response()->json(['message' => 'Padre/tutor eliminado correctamente']);
        });
    }

    /**
     * Vincular estudiante a padre
     */
    public function attachStudent(Request $request, $id)
    {
        $parent = ParentModel::findOrFail($id);

        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'relationship' => ['required', Rule::in(['padre', 'madre', 'tutor', 'abuelo', 'otro'])],
        ]);

        // Verificar si ya está vinculado
        if ($parent->students()->where('students.id', $data['student_id'])->exists()) {
            return response()->json(['message' => 'El estudiante ya está vinculado'], 422);
        }

        $parent->students()->attach($data['student_id'], ['relationship' => $data['relationship']]);

        return response()->json([
            'message' => 'Estudiante vinculado correctamente',
            'parent' => $parent->fresh()->load('students')
        ]);
    }

    /**
     * Desvincular estudiante de padre
     */
    public function detachStudent($id, $studentId)
    {
        $parent = ParentModel::findOrFail($id);
        $parent->students()->detach($studentId);

        return response()->json([
            'message' => 'Estudiante desvinculado correctamente',
            'parent' => $parent->fresh()->load('students')
        ]);
    }

    /**
     * Buscar padres por CI o nombre
     */
    public function search(Request $request)
    {
        $term = $request->input('q', '');

        if (strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $parents = ParentModel::with('user')
            ->where('ci', 'like', "%{$term}%")
            ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$term}%"))
            ->limit(10)
            ->get();

        return response()->json(['data' => $parents]);
    }
}
