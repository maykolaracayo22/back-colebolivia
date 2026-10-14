<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Preinscription;
use App\Models\User;
use App\Models\StudentAccount;
use App\Models\AccountCharge;
use App\Models\FeeConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Inscripción
 * 
 * Convierte pre-inscripciones aprobadas en estudiantes completos del sistema.
 * Crea usuarios para padre e hijo, cuenta financiera y genera cargos automáticos.
 */
class EnrollmentService
{
    /**
     * Convierte una preinscripción en estudiante completo
     * 
     * @param Preinscription $preinscription
     * @param int $year Año de inscripción
     * @param string|null $paralelo Paralelo asignado (A, B, C, etc)
     * @return array Resultado con student_id, padre_user_id, student_user_id
     */
    public function convertToStudent(Preinscription $preinscription, int $year, ?string $paralelo = null): array
    {
        Log::info('📝 Iniciando conversión preinscripción → estudiante', [
            'preinscription_id' => $preinscription->id,
            'codigo' => $preinscription->codigo_preinscripcion
        ]);

        DB::beginTransaction();

        try {
            // 1. Crear usuario PADRE
            $padreUser = $this->crearUsuarioPadre($preinscription);

            // 2. Crear usuario ESTUDIANTE
            $estudianteUser = $this->crearUsuarioEstudiante($preinscription);

            // 3. Crear registro Student
            $student = $this->crearStudent($preinscription, $estudianteUser->id, $year, $paralelo);

            // 4. Crear cuenta financiera
            $studentAccount = $this->crearCuentaFinanciera($student, $year);

            // 5. Generar cargos automáticos (1 matrícula + 10 mensualidades)
            $cargos = $this->generarCargos($studentAccount, $preinscription->nivel, $year);

            // 6. Marcar preinscripción como convertida
            $preinscription->update([
                'convertida_a_student_id' => $student->id,
                'estado' => 'convertido'
            ]);

            DB::commit();

            Log::info('✅ Conversión completada exitosamente', [
                'student_id' => $student->id,
                'padre_user_id' => $padreUser->id,
                'cargos_generados' => count($cargos)
            ]);

            return [
                'success' => true,
                'student_id' => $student->id,
                'student' => $student,
                'padre_user_id' => $padreUser->id,
                'estudiante_user_id' => $estudianteUser->id,
                'cuenta_id' => $studentAccount->id,
                'cargos_generados' => count($cargos),
                'total_deuda' => $studentAccount->total_debt
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('❌ Error en conversión preinscripción', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Crea usuario padre con credenciales automáticas
     */
    protected function crearUsuarioPadre(Preinscription $preinscription): User
    {
        $username = $this->generarUsername(
            $preinscription->tutor_nombre,
            'PADRE'
        );

        $password = $this->generarPasswordPadre($preinscription->tutor_ci ?? $username);

        $user = User::create([
            'name' => $preinscription->tutor_nombre,
            'email' => $this->generarEmailPadre($username),
            'password' => Hash::make($password),
            'username' => $username,
            'password_temp' => $password, // Guardar temporal para enviar
            'must_change_password' => true,
            'ci' => $preinscription->tutor_ci,
            'telefono' => $preinscription->tutor_telefono
        ]);

        // Asignar rol PADRE
        $user->assignRole('Padre');

        Log::info('�� Usuario padre creado', [
            'user_id' => $user->id,
            'username' => $username
        ]);

        return $user;
    }

    /**
     * Crea usuario estudiante con credenciales automáticas
     */
    protected function crearUsuarioEstudiante(Preinscription $preinscription): User
    {
        $username = $this->generarUsername(
            $preinscription->apellidos . ' ' . $preinscription->nombres,
            'ESTUDIANTE'
        );

        $password = $this->generarPasswordEstudiante($preinscription->ci ?? $username);

        $user = User::create([
            'name' => $preinscription->nombres . ' ' . $preinscription->apellidos,
            'email' => $this->generarEmailEstudiante($username),
            'password' => Hash::make($password),
            'username' => $username,
            'password_temp' => $password,
            'must_change_password' => true,
            'ci' => $preinscription->ci,
            'telefono' => $preinscription->telefono
        ]);

        // Asignar rol ESTUDIANTE
        $user->assignRole('Estudiante');

        Log::info('👤 Usuario estudiante creado', [
            'user_id' => $user->id,
            'username' => $username
        ]);

        return $user;
    }

    /**
     * Crea registro Student con todos los datos
     */
    protected function crearStudent(Preinscription $preinscription, int $userId, int $year, ?string $paralelo): Student
    {
        $student = Student::create([
            'user_id' => $userId,
            'rude' => null, // Se asigna después con RUDE oficial
            'ci' => $preinscription->ci,
            'nombres' => $preinscription->nombres,
            'apellidos' => $preinscription->apellidos,
            'sexo' => $preinscription->sexo,
            'fecha_nac' => $preinscription->fecha_nac,
            'nacionalidad' => $preinscription->nacionalidad,
            'dep_nac' => $preinscription->dep_nac,
            'muni_nac' => $preinscription->muni_nac,
            'direccion' => $preinscription->direccion,
            'telefono' => $preinscription->telefono,
            'email' => $preinscription->email,
            'pueblo_indigena' => $preinscription->pueblo_indigena,
            'discapacidad' => $preinscription->discapacidad ?? false,
            'idiomas' => $preinscription->idiomas ?? [],
            'vulnerabilidad' => $preinscription->vulnerabilidad ?? false,
            'tutor_nombre' => $preinscription->tutor_nombre,
            'tutor_ci' => $preinscription->tutor_ci,
            'tutor_parentesco' => $preinscription->tutor_parentesco,
            'tutor_telefono' => $preinscription->tutor_telefono,
            'nivel' => $preinscription->nivel,
            'curso' => $preinscription->grado,
            'paralelo' => $paralelo,
            'activo' => true,
            'anio_ingreso' => $year,
            'mes_ingreso' => now()->month
        ]);

        Log::info('🎓 Registro Student creado', ['student_id' => $student->id]);

        return $student;
    }

    /**
     * Crea cuenta financiera del estudiante
     */
    protected function crearCuentaFinanciera(Student $student, int $year): StudentAccount
    {
        $account = StudentAccount::create([
            'student_id' => $student->id,
            'year' => $year,
            'total_debt' => 0, // Se calcula al agregar cargos
            'total_paid' => 0,
            'status' => 'AL_DIA'
        ]);

        Log::info('💰 Cuenta financiera creada', ['account_id' => $account->id]);

        return $account;
    }

    /**
     * Genera cargos automáticos: 1 matrícula + 10 mensualidades
     */
    protected function generarCargos(StudentAccount $studentAccount, string $nivel, int $year): array
    {
        $cargos = [];

        // 1. MATRÍCULA (enero)
        $matriculaConfig = FeeConfig::where('year', $year)
            ->where('nivel', $nivel)
            ->where('concepto', 'MATRICULA')
            ->first();

        if ($matriculaConfig) {
            $cargo = AccountCharge::create([
                'student_account_id' => $studentAccount->id,
                'concept' => 'MATRICULA',
                'month' => 1,
                'year' => $year,
                'description' => "Matrícula {$year}",
                'amount' => $matriculaConfig->monto,
                'paid_amount' => 0,
                'due_date' => "{$year}-01-31",
                'status' => 'PENDING',
                'created_by' => auth()->id() ?? 1
            ]);
            $cargos[] = $cargo;
        }

        // 2. MENSUALIDADES (febrero - noviembre = 10 meses)
        $mensualidadConfig = FeeConfig::where('year', $year)
            ->where('nivel', $nivel)
            ->where('concepto', 'MENSUALIDAD')
            ->first();

        if ($mensualidadConfig) {
            $meses = ['02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril', '05' => 'Mayo', 
                      '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto', '09' => 'Septiembre',
                      '10' => 'Octubre', '11' => 'Noviembre'];

            foreach ($meses as $mes => $nombre) {
                $cargo = AccountCharge::create([
                    'student_account_id' => $studentAccount->id,
                    'concept' => 'MENSUALIDAD',
                    'month' => (int)$mes,
                    'year' => $year,
                    'description' => "Mensualidad {$nombre} {$year}",
                    'amount' => $mensualidadConfig->monto,
                    'paid_amount' => 0,
                    'due_date' => "{$year}-{$mes}-10", // Vence día 10 de cada mes
                    'status' => 'PENDING',
                    'created_by' => auth()->id() ?? 1
                ]);
                $cargos[] = $cargo;
            }
        }

        // Actualizar total_debt de la cuenta
        $totalDeuda = array_sum(array_column($cargos, 'amount'));
        $studentAccount->update(['total_debt' => $totalDeuda]);

        Log::info('💵 Cargos generados', [
            'cantidad' => count($cargos),
            'total' => $totalDeuda
        ]);

        return $cargos;
    }

    /**
     * Genera username único
     */
    protected function generarUsername(string $nombre, string $tipo): string
    {
        $base = strtolower(str_replace(' ', '', $nombre));
        $base = preg_replace('/[^a-z0-9]/', '', $base);
        $base = substr($base, 0, 15);
        
        $sufijo = substr($tipo, 0, 1) . rand(100, 999);
        
        return $base . $sufijo;
    }

    /**
     * Genera email temporal para padre
     */
    protected function generarEmailPadre(string $username): string
    {
        return $username . '@padres.colegio.local';
    }

    /**
     * Genera email temporal para estudiante
     */
    protected function generarEmailEstudiante(string $username): string
    {
        return $username . '@estudiantes.colegio.local';
    }

    /**
     * Genera contraseña para padre (CI + año actual)
     */
    protected function generarPasswordPadre(string $ci): string
    {
        return substr($ci, 0, 6) . date('Y');
    }

    /**
     * Genera contraseña para estudiante (CI + 2025)
     */
    protected function generarPasswordEstudiante(string $ci): string
    {
        return substr($ci, 0, 6) . '2025';
    }
}
