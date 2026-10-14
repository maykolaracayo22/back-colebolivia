<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Annotations as OA;
/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Colegio API Documentation",
 *     description="API para gestión integral de colegio - Sistema de gestión académica, administrativa y financiera",
 *     @OA\Contact(
 *         email="admin@colegio.com"
 *     ),
 *     @OA\License(
 *         name="Private",
 *         url=""
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Ingrese el token Bearer obtenido del endpoint de login"
 * )
 *
 * @OA\Tag(name="Autenticación", description="Endpoints para autenticación y gestión de sesiones")
 * @OA\Tag(name="Actas", description="Gestión de actas de calificaciones")
 * @OA\Tag(name="Evaluaciones", description="Gestión de evaluaciones de actas")
 * @OA\Tag(name="Notas", description="Gestión de notas de estudiantes")
 * @OA\Tag(name="Estudiantes", description="Gestión de estudiantes")
 * @OA\Tag(name="Docentes", description="Gestión de docentes")
 * @OA\Tag(name="Enrollments", description="Gestión de matrículas/inscripciones de estudiantes")
 * @OA\Tag(name="Roles", description="Gestión de roles y permisos")
 * @OA\Tag(name="Centralizador", description="Gestión del centralizador de notas")
 * @OA\Tag(name="Auditoría", description="Registro de eventos de auditoría")
 * @OA\Tag(name="Promoción", description="Gestión de promoción de estudiantes")
 * @OA\Tag(name="Exportaciones", description="Exportación de datos y reportes")
 * @OA\Tag(name="Perfil", description="Información del usuario autenticado")
 * @OA\Tag(name="Subjects", description="Gestión de materias del plan de estudios")
 * @OA\Tag(name="Periods", description="Gestión de periodos académicos")
 * @OA\Tag(name="Grades", description="Gestión de calificaciones resumen")
 * @OA\Tag(name="Attendance", description="Gestión de asistencia de estudiantes")
 * @OA\Tag(name="Finance", description="Gestión financiera: configuraciones, cargos, pagos y reportes")
 * @OA\Tag(name="Conduct", description="Gestión de conducta estudiantil")
 * @OA\Tag(name="Preinscriptions", description="Gestión de preinscripciones y RUDE")
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
