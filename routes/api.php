<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Api\ActaController;
use App\Http\Controllers\Api\ActaEvaluationController;
use App\Http\Controllers\Api\ActaNoteController;
use App\Http\Controllers\Api\CentralizadorController;
use App\Http\Controllers\Api\AuditEventController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\PreinscriptionController;
use App\Http\Controllers\FeeConfigController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ConductController;
use App\Http\Controllers\WhatsappController;
use App\Http\Controllers\PromocionController;
use App\Http\Controllers\ParentModelController;
use App\Http\Controllers\CourseController;

// (si creas mÃ³dulos nuevos: PreregController, RudeController, ReportsController, FeesController, ConductController, etc)

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/ping', fn () => response()->json(['ok' => true, 'time' => now()->toISOString()]));

Route::get('/announcements', [AnnouncementController::class, 'publicIndex']);
Route::middleware(['auth:sanctum', 'role:admin|SuperAdmin|Director General|director_general'])->group(function () {
    Route::get('/admin/announcements', [AnnouncementController::class, 'index']);
    Route::post('/admin/announcements', [AnnouncementController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| ACCESO PADRES/TUTORES (sin autenticación tradicional)
|--------------------------------------------------------------------------
| Los padres acceden con el código del estudiante para consultar información
*/
Route::post('/parent/lookup', function (Request $request) {
    $request->validate([
        'student_code' => 'required|string',
    ]);

    // Los códigos se comparten y copian con frecuencia; normalizar espacios
    // y mayúsculas evita que una diferencia de formato impida encontrarlo.
    $code = trim((string) $request->input('student_code'));
    $normalizedCode = mb_strtoupper(preg_replace('/\s+/u', '', $code));

    // Aceptar el código de acceso generado por el sistema y los códigos RUDE
    // que se muestran en las fichas de estudiantes. Mantener usuario/email
    // como alternativas para cuentas creadas con versiones anteriores.
    $student = \App\Models\Student::query()
        ->whereRaw("UPPER(REPLACE(TRIM(codigo), ' ', '')) = ?", [$normalizedCode])
        ->orWhereRaw("UPPER(REPLACE(TRIM(codigo_rude), ' ', '')) = ?", [$normalizedCode])
        ->orWhereRaw("UPPER(REPLACE(TRIM(rude), ' ', '')) = ?", [$normalizedCode])
        ->orWhereRaw("UPPER(REPLACE(TRIM(usuario), ' ', '')) = ?", [$normalizedCode])
        ->orWhereRaw("UPPER(REPLACE(TRIM(email), ' ', '')) = ?", [$normalizedCode])
        ->first();

    if (!$student) {
        return response()->json([
            'success' => false,
            'message' => 'Estudiante no encontrado. Verifica el código ingresado.'
        ], 404);
    }

    // Generar token temporal para consultas (válido por 1 hora)
    $tempToken = hash('sha256', $student->codigo . now()->timestamp . config('app.key'));

    return response()->json([
        'success' => true,
        'token' => 'parent_' . $tempToken,
        'mustChangePassword' => false,
        'user' => [
            'id' => $student->id,
            'name' => $student->nombres . ' ' . $student->apellidos,
            'email' => $student->email,
            'username' => $student->codigo,
            'role' => 'parent',
            'abilities' => ['child:read', 'reports:read'],
            'teacherId' => null,
            'studentId' => $student->id,
            'student' => [
                'id' => $student->id,
                'nombre' => $student->nombres,
                'apellido' => $student->apellidos,
                'codigo' => $student->codigo,
                'nivel' => $student->nivel,
                'curso' => $student->curso,
                'paralelo' => $student->paralelo,
                'ci' => $student->ci,
                'rude' => $student->rude,
                'codigo_rude' => $student->codigo_rude,
                'sexo' => $student->sexo,
                'fecha_nac' => $student->fecha_nac,
                'email' => $student->email,
                'telefono' => $student->telefono,
                'direccion' => $student->direccion,
                'nacionalidad' => $student->nacionalidad,
                'dep_nac' => $student->dep_nac,
                'muni_nac' => $student->muni_nac,
                'anio_ingreso' => $student->anio_ingreso,
                'mes_ingreso' => $student->mes_ingreso,
                'tutor_nombre' => $student->tutor_nombre,
                'tutor_ci' => $student->tutor_ci,
                'tutor_parentesco' => $student->tutor_parentesco,
                'tutor_telefono' => $student->tutor_telefono,
            ],
        ],
        'student' => [
            'id' => $student->id,
            'codigo' => $student->codigo,
            'nombres' => $student->nombres,
            'apellidos' => $student->apellidos,
            'nivel' => $student->nivel,
            'curso' => $student->curso,
            'paralelo' => $student->paralelo,
            'ci' => $student->ci,
            'rude' => $student->rude,
            'codigo_rude' => $student->codigo_rude,
            'sexo' => $student->sexo,
            'fecha_nac' => $student->fecha_nac,
            'email' => $student->email,
            'telefono' => $student->telefono,
            'direccion' => $student->direccion,
            'nacionalidad' => $student->nacionalidad,
            'dep_nac' => $student->dep_nac,
            'muni_nac' => $student->muni_nac,
            'anio_ingreso' => $student->anio_ingreso,
            'mes_ingreso' => $student->mes_ingreso,
            'tutor_nombre' => $student->tutor_nombre,
            'tutor_ci' => $student->tutor_ci,
            'tutor_parentesco' => $student->tutor_parentesco,
            'tutor_telefono' => $student->tutor_telefono,
        ],
    ]);
});

// Auth - Autenticación principal
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LevelController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;

// Endpoints públicos de autenticación
Route::post('/login', [AuthController::class, 'login']);

// Endpoints protegidos de autenticación
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/student/context', [StudentController::class, 'myContext']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/user', fn (Request $r) => $r->user());

    // Niveles disponibles para la UI de docentes
    Route::get('/levels', [LevelController::class, 'index']);
    // Docente autenticado: ver su perfil
    Route::get('/teachers/me', [TeacherController::class, 'me']);
    // ✅ Docente autenticado: ver SUS niveles (SIN permission extra)
    Route::get('/teachers/me/levels', [TeacherController::class, 'levels']);

    // ✅ SOLO admin o director pueden modificar niveles
    Route::post('/teachers/me/levels', [TeacherController::class, 'setLevels'])
        ->middleware(['role:admin,director']);

    Route::get('/teacher/sections', [TeacherController::class, 'sections']); // Added route
    Route::get('/teacher/sections/{section}/students', [TeacherController::class, 'sectionStudents']);
    // Lectura del libro de calificaciones propio; no requiere permiso de edición.
    Route::get('/teacher/gradebook/actas', [ActaController::class, 'myGradebookIndex']);
    Route::get('/teacher/gradebook/actas/{acta}', [ActaController::class, 'myGradebookShow']);
    // Periodos de solo lectura necesarios para seleccionar el trimestre.
    Route::get('/teacher/periods', [PeriodController::class, 'index']);
    // El controlador/policy limita las modificaciones al docente propietario del acta.
    Route::put('/actas/{id}/items', [ActaController::class, 'saveItems'])->name('actas.saveItems');
    Route::apiResource('acta-evaluations', ActaEvaluationController::class)->only(['store', 'update', 'destroy']);
});

// Login legacy (mantener por compatibilidad)
Route::post('/legacy-login',  [LoginController::class, 'login'])->name('api.login');

/*
|--------------------------------------------------------------------------
| ADMIN (catálogos, full gestión técnica)
|--------------------------------------------------------------------------
*/
// Endpoints protegidos por credenciales escolares
Route::middleware(['auth:sanctum', 'must_change_password'])->group(function () {
    Route::middleware('role:admin,director')->group(function () {
        Route::post('/teachers', [TeacherController::class, 'store']);
        Route::post('/students', [StudentController::class, 'store']);

    });
    // ...otras rutas protegidas por rol
    // Gestión de roles
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);
    Route::post('/users/{id}/roles', [RoleController::class, 'assignToUser']);
    Route::delete('/users/{id}/roles', [RoleController::class, 'removeFromUser']);

    // Gestión de permisos
    Route::get('/permissions', [PermissionController::class, 'index']);
    Route::post('/permissions', [PermissionController::class, 'store']);
    Route::post('/roles/{id}/permissions', [PermissionController::class, 'assignToRole']);
    Route::delete('/roles/{id}/permissions', [PermissionController::class, 'removeFromRole']);
});

// Legacy admin endpoints - Admin tiene acceso completo
Route::middleware(['auth:sanctum','role:Admin|Director General|SuperAdmin'])->group(function () {
    Route::apiResource('students',    StudentController::class);
    Route::apiResource('teachers',    TeacherController::class);
    Route::apiResource('subjects',    SubjectController::class);
    Route::apiResource('sections',    SectionController::class);
    Route::apiResource('enrollments', EnrollmentController::class)->only(['index','store','show','update','destroy']);
    Route::apiResource('periods',     PeriodController::class)->only(['index','store','update']);

    // CRUD de Cursos (Grados/Años de Escolaridad)
    Route::apiResource('courses', CourseController::class);
    Route::post('courses/generar-estandar', [CourseController::class, 'generarEstandar']);

    // Otros recursos técnicos...
});

/*
|--------------------------------------------------------------------------
| DOCENTES + DIRECCION (actas, notas, assignments, asistencia)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum','permission:notas.editar|notas.crear'])->group(function () {
    Route::apiResource('actas', ActaController::class)->only(['index','store','show','update']);
    Route::post('actas/{id}/lock',     [ActaController::class, 'lock'])->name('actas.lock');
    Route::post('actas/{id}/validate', [ActaController::class, 'validateActa'])->name('actas.validate');
    Route::post('actas/{id}/recompute', [ActaController::class, 'recompute'])->name('actas.recompute');

    // Centralizador (resúmenes por curso/alumno)
    Route::get('centralizador/course', [CentralizadorController::class, 'byCourse'])->name('centralizador.course');
    Route::get('centralizador/student/{student_id}', [CentralizadorController::class, 'byStudent'])->name('centralizador.student');

    // Publicar solo admin o director_general:
    Route::post('actas/{id}/publish',  [ActaController::class, 'publish'])
        ->middleware('permission:notas.consolidar')->name('actas.publish');

    // Notas
    Route::apiResource('acta-notes', ActaNoteController::class)->only(['store','update','destroy']);

    // Publicar solo admin o director_general:
    Route::post('actas/{id}/publish',  [ActaController::class, 'publish'])
        ->middleware('permission:notas.consolidar')->name('actas.publish');

});

// Asistencia: la autorización se aplica en AttendancePolicy y en el reporte propio.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('attendance', [AttendanceController::class, 'index']);
    Route::post('attendance', [AttendanceController::class, 'store']);
    Route::post('attendance/bulk', [AttendanceController::class, 'bulkStore']);
    Route::get('attendance/me', [AttendanceController::class, 'myReport']);
    Route::get('attendance/report/{studentId}', [AttendanceController::class, 'report']);
});

// Las asignaciones tienen autorización propia en AssignmentPolicy. No deben
// depender de los permisos de edición de notas, que son de otro módulo.
Route::apiResource('assignments', AssignmentController::class)
    ->middleware('auth:sanctum');

// Auditoría para roles con permiso
Route::middleware(['auth:sanctum','permission:admin.auditoria'])->group(function () {
    Route::get('audit-events',  [AuditEventController::class, 'index']);
    Route::post('audit-events', [AuditEventController::class, 'store']);

});

/*
|--------------------------------------------------------------------------
| SecretarÃ­a / DirecciÃ³n (preinscripciÃ³n, RUDE, centralizador)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum','role:secretaria|director_academico|director_general|admin'])->group(function () {
    // Route::post('prereg', [PreregController::class, 'store']);
    // Route::get('rude/{id}', [RudeController::class, 'show']);
    // Route::post('rude/{id}/upload', [RudeController::class, 'upload']);
    // Route::get('reports/centralizador', [ReportsController::class,'centralizador']);
});

/*
|--------------------------------------------------------------------------
| Finanzas
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum','role:finanzas|director_general|admin'])->group(function () {
    // Generar cargos masivos
    Route::post('fees/generate', [FinanceController::class,'generateFees']);

    // Reporte central (PDF)
    Route::get('fees/reports/central', [FinanceController::class,'centralReport']);

    // Estado de cuenta por estudiante
    Route::get('fees/{studentId}', [FinanceController::class,'showLedger']);

    // Registrar pago (usa registrarPago)
    Route::post('fees/{studentId}', [FinanceController::class,'registrarPago']);

    // Anular pago
    Route::patch('fees/{paymentId}/cancel', [FinanceController::class,'cancelPayment']);

    //Pago
    Route::post('fees/pagos',       [FinanceController::class,'registrarPago']);

    // Extras del controlador que ya tienes:
    Route::get('finance/reports/summary', [FinanceController::class,'reporteSummary']);
    Route::get('finance/accounts/{accountId}/estado', [FinanceController::class,'estadoCuenta']);
    Route::get('finance/charges/pending', [FinanceController::class,'cargosPendientes']);
    Route::get('finance/charges/overdue', [FinanceController::class,'cargosVencidos']);
    Route::get('finance/payments/{payment}/receipt', [FinanceController::class, 'receipt']);
});

/*
|--------------------------------------------------------------------------
| Regente (listas / conducta)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum','role:regente|director_academico|Director General|admin|Admin|SuperAdmin'])->group(function () {
    // Route::get ('lists',   [ClassListController::class,'index']);
    Route::apiResource('conduct', ConductController::class);
    Route::patch('conduct/{id}/approve', [ConductController::class, 'approve']);
});

/*
|--------------------------------------------------------------------------
| Preinscripciones y RUDE
|--------------------------------------------------------------------------
*/
Route::post('preinscriptions', [PreinscriptionController::class, 'store']);
Route::get('preinscriptions/{codigo}', [PreinscriptionController::class, 'show']);
Route::get('preinscriptions/{codigo}/rude', [PreinscriptionController::class, 'downloadRude']);

Route::middleware(['auth:sanctum','role:Admin|SuperAdmin|Director General|secretaria|director_academico'])->group(function () {
    Route::get('preinscriptions', [PreinscriptionController::class, 'index']);
    Route::patch('preinscriptions/{id}/approve', [PreinscriptionController::class, 'approve']);
    Route::patch('preinscriptions/{id}/reject', [PreinscriptionController::class, 'reject']);
});

/*
|--------------------------------------------------------------------------
| Secciones (helpers de lectura)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('sections/cursos',    [SectionController::class, 'cursos']);     // year, nivel
    Route::get('sections/paralelos', [SectionController::class, 'paralelos']);  // year, nivel, curso
    Route::get('sections/{section}/students', [SectionController::class, 'students']); // estudiantes de sección
    Route::post('sections/duplicate-year', [SectionController::class, 'duplicateYear']); // duplicar secciones
});

/*
|--------------------------------------------------------------------------
| Asistencia y WhatsApp
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'permission:comunicaciones.whatsapp.enviar|comunicaciones.ver_historial'])->group(function () {
    Route::post('whatsapp/send', [WhatsappController::class, 'sendMessage']);
    Route::post('whatsapp/bulk', [WhatsappController::class, 'sendBulk']);
    Route::get('whatsapp/logs', [WhatsappController::class, 'logs']);
});

/*
|--------------------------------------------------------------------------
| Lectura para Student/Parent (reportes propios)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum','role:student|parent|teacher|director_academico|director_general|admin'])->group(function () {
    // Route::get('reports/centralizador/{studentId}', [ReportsController::class,'centralizadorAlumno']);
});

/*
|--------------------------------------------------------------------------
| Fallback
|--------------------------------------------------------------------------
*/
Route::fallback(fn() => response()->json(['message' => 'Ruta no encontrada.'], 404));
Route::middleware(['auth:sanctum','permission:promotion.run'])
    ->post('promotions/run', [PromotionController::class, 'run']);


// Prueba de PDF
Route::get('pdf-test', function () {
    return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.test', ['msg' => 'Hola desde PDF'])
            ->download('test.pdf');
});
// Test routes for roles
Route::middleware('auth:sanctum')->get('/test-role', function () {
    return response()->json([
        'user' => auth()->user()->name,
        'roles' => auth()->user()->roles->pluck('name'),
        'permissions' => auth()->user()->permissions->pluck('name'),
    ]);
});

Route::middleware(['auth:sanctum', 'role:Director General'])->get('/test-director-only', fn() => response()->json(['message' => 'Solo Director General puede acceder']));

Route::middleware(['auth:sanctum', 'role:Docente'])->get('/test-docente-only', fn() => response()->json(['message' => 'Solo Docente puede acceder']));

Route::middleware(['auth:sanctum', 'permission:notas.rectificar'])->get('/test-permission-rectificar', fn() => response()->json(['message' => 'Puedes rectificar notas']));





// ============================================================
// RUTAS DE PROMOCIONES (Promoci�n de estudiantes entre gestiones)
// ============================================================
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('promociones', PromocionController::class);
    Route::post('promociones/{promocion}/aprobar', [PromocionController::class, 'aprobar']);
    Route::post('promociones/{promocion}/rechazar', [PromocionController::class, 'rechazar']);
    Route::post('promociones/masiva', [PromocionController::class, 'promocionMasiva']);
});

// ============================================================
// RUTAS DE PADRES DE FAMILIA
// ============================================================
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('parents', ParentModelController::class);
    Route::get('parents-search', [ParentModelController::class, 'search']);
    Route::post('parents/{id}/attach-student', [ParentModelController::class, 'attachStudent']);
    Route::delete('parents/{id}/detach-student/{studentId}', [ParentModelController::class, 'detachStudent']);
});

/*
|--------------------------------------------------------------------------
| Preinscripciones y RUDE
|--------------------------------------------------------------------------
*/
Route::post('preinscriptions', [PreinscriptionController::class, 'store']);
Route::get('preinscriptions/{codigo}', [PreinscriptionController::class, 'show']);
Route::get('preinscriptions/{codigo}/rude', [PreinscriptionController::class, 'downloadRude']);

Route::middleware(['auth:sanctum','role:admin|secretaria|director_academico'])->group(function () {
    Route::patch('preinscriptions/{id}/approve', [PreinscriptionController::class, 'approve']);
    Route::patch('preinscriptions/{id}/reject', [PreinscriptionController::class, 'reject']);
});
