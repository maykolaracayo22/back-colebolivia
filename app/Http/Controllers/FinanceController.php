<?php

namespace App\Http\Controllers;

use App\Models\StudentAccount;
use App\Models\AccountCharge;
use App\Models\FeeConfig;
use App\Models\Payment;
use App\Models\Student;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenApi\Annotations as OA;
use Carbon\Carbon;

class FinanceController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * @OA\Get(
     *     path="/api/fees/{studentId}",
     *     summary="Ver estado de cuenta del estudiante",
     *     description="Obtiene el estado de cuenta del estudiante para un año dado.",
     *     operationId="getStudentLedger",
     *     tags={"Finance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="studentId", in="path", required=true, description="ID del estudiante", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="year", in="query", required=false, description="Año (YYYY)", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Estado de cuenta retornado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Cuenta no encontrada")
     * )
     */
    public function showLedger($studentId, Request $request)
    {
        $student = Student::findOrFail($studentId);
        $this->authorize('view', $student);

        $year = $request->input('year', now()->year);

        $account = StudentAccount::where('student_id', $studentId)
            ->where('year', $year)
            ->with(['accountCharges' => function($q) {
                $q->orderBy('due_date', 'asc');
            }, 'payments' => function($q) {
                $q->orderBy('payment_date', 'desc');
            }])
            ->first();

        if (!$account) {
            return response()->json([
                'message' => 'No se encontrÃ³ cuenta financiera para este estudiante en el aÃ±o ' . $year,
                'student' => $student->only(['id', 'nombres', 'apellidos']),
                'year' => $year
            ], 404);
        }

        // Usar el servicio para obtener el estado de cuenta
        $estadoCuenta = $this->paymentService->estadoCuenta($account);

        return response()->json([
            'student' => $student->only(['id', 'nombres', 'apellidos']),
            'account' => $account,
            'estado_cuenta' => $estadoCuenta,
            'year' => $year
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/fees/{studentId}",
     *     summary="Registrar pago",
     *     description="Registra un pago para la cuenta del estudiante con distribución automática.",
     *     operationId="recordPayment",
     *     tags={"Finance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="studentId", in="path", required=true, description="ID del estudiante", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"student_account_id","amount","payment_method"},
     *             @OA\Property(property="student_account_id", type="integer", example=10),
     *             @OA\Property(property="amount", type="number", format="float", example=100.00),
     *             @OA\Property(property="payment_method", type="string", enum={"EFECTIVO","TRANSFERENCIA","QR","TARJETA"}),
     *             @OA\Property(property="reference", type="string", nullable=true),
     *             @OA\Property(property="payment_date", type="string", format="date", nullable=true),
     *             @OA\Property(property="notes", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Pago registrado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */

    public function registrarPago(Request $request)
    {
        $data = $request->validate([
            'student_account_id' => 'required|exists:student_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:EFECTIVO,TRANSFERENCIA,QR,TARJETA',
            'reference' => 'nullable|string|max:100',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500'
        ]);

        $account = StudentAccount::findOrFail($data['student_account_id']);

        // Verificar autorizaciÃ³n
        $this->authorize('view', $account->student);

        try {
            Log::info('ðŸ’³ Registrando pago', [
                'account_id' => $account->id,
                'student_id' => $account->student_id,
                'amount' => $data['amount'],
                'method' => $data['payment_method']
            ]);

            // Registrar el pago usando el servicio
            $resultado = $this->paymentService->registrarPago(
                $account,
                $data['amount'],
                $data['payment_method'],
                $data['reference'] ?? null,
                auth()->id()
            );

            Log::info('âœ… Pago registrado exitosamente', [
                'payment_id' => $resultado['payment']->id,
                'receipt' => $resultado['payment']->receipt_number,
                'cargos_aplicados' => count($resultado['items_aplicados'])
            ]);

            return response()->json([
                'message' => 'Pago registrado exitosamente',
                'payment' => $resultado['payment'],
                'items_aplicados' => $resultado['items_aplicados'],
                'estado_cuenta' => $resultado['estado_cuenta']
            ], 201);

        } catch (\Exception $e) {
            Log::error('âŒ Error al registrar pago: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error al registrar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/finance/accounts/{accountId}/estado
     * Obtener estado de cuenta detallado
     */
    public function estadoCuenta($accountId)
    {
        $account = StudentAccount::with(['student', 'accountCharges', 'payments'])
            ->findOrFail($accountId);

        $this->authorize('view', $account->student);

        $estadoCuenta = $this->paymentService->estadoCuenta($account);

        return response()->json($estadoCuenta);
    }

    /**
     * @OA\Get(
     *     path="/api/finance/payments/{paymentId}/receipt",
     *     summary="Generar recibo de pago",
     *     description="Genera el recibo de pago en PDF.",
     *     operationId="generatePaymentReceipt",
     *     tags={"Finance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="paymentId", in="path", required=true, description="ID del pago", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="PDF del recibo"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Pago no encontrado")
     * )
     */
    public function generarRecibo($paymentId)
    {
        $payment = Payment::with(['studentAccount.student', 'paymentItems.accountCharge'])
            ->findOrFail($paymentId);

        $this->authorize('view', $payment->studentAccount->student);

        try {
            $pdfContent = $this->paymentService->generarRecibo($payment);

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="recibo_' . $payment->receipt_number . '.pdf"');

        } catch (\Exception $e) {
            Log::error('âŒ Error al generar recibo: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error al generar el recibo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Return receipt JSON for a payment.
     *
     * @param int $paymentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function receipt($paymentId)
    {
        $payment = Payment::with([
            'studentAccount.student',
            'items.charge'
        ])->findOrFail($paymentId);

        return response()->json([
            'student' => [
                'id' => $payment->studentAccount->student->id,
                'name' => $payment->studentAccount->student->name,
                'grade' => $payment->studentAccount->student->grade,
            ],
            'payment' => [
                'id' => $payment->id,
                'date' => $payment->created_at->toDateString(),
                'method' => $payment->method,
                'total' => $payment->total,
            ],
            'items' => $payment->items->map(function ($item) {
                return [
                    'description' => $item->charge->description,
                    'amount' => $item->amount,
                ];
            }),
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/api/fees/{paymentId}/cancel",
     *     summary="Anular pago",
     *     description="Anula un pago y reversa su distribución.",
     *     operationId="cancelPayment",
     *     tags={"Finance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="paymentId", in="path", required=true, description="ID del pago", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Pago anulado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Pago no encontrado"),
     *     @OA\Response(response=422, description="Error de negocio")
     * )
     */
    public function cancelPayment($paymentId)
    {
        $payment = Payment::with('paymentItems.accountCharge')->findOrFail($paymentId);

        $this->authorize('update', Payment::class);

        try {
            DB::beginTransaction();

            Log::info('ðŸ”„ Anulando pago', ['payment_id' => $payment->id]);

            // Reversar los items del pago
            foreach ($payment->paymentItems as $item) {
                $charge = $item->accountCharge;

                // Restar el monto aplicado
                $charge->paid_amount -= $item->amount_applied;

                // Actualizar el estado del cargo
                if ($charge->paid_amount <= 0) {
                    $charge->status = AccountCharge::STATUS_PENDING;
                    $charge->paid_amount = 0;
                } elseif ($charge->paid_amount < $charge->amount) {
                    $charge->status = AccountCharge::STATUS_PARTIAL;
                } else {
                    $charge->status = AccountCharge::STATUS_PAID;
                }

                $charge->save();
            }

            // Actualizar la cuenta
            $account = $payment->studentAccount;
            $account->total_paid -= $payment->amount;
            $account->save();

            // Recalcular estado de la cuenta
            $this->paymentService->actualizarEstadoCuenta($account);

            // Marcar el pago como anulado (opcional: agregar campo 'canceled_at' en migration)
            // Por ahora solo agregamos nota
            $payment->notes = ($payment->notes ?? '') . "\n[ANULADO el " . now()->format('Y-m-d H:i') . " por usuario " . auth()->id() . "]";
            $payment->save();

            DB::commit();

            Log::info('âœ… Pago anulado exitosamente');

            return response()->json([
                'message' => 'Pago anulado exitosamente',
                'payment' => $payment
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('âŒ Error al anular pago: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error al anular el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/finance/reports/summary",
     *     summary="Reporte consolidado (JSON)",
     *     description="Devuelve resumen financiero del año.",
     *     operationId="financeReportSummary",
     *     tags={"Finance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="year", in="query", required=false, description="Año (YYYY)", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Resumen retornado"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function reporteSummary(Request $request)
    {
        $this->authorize('viewAny', StudentAccount::class);

        $year = $request->input('year', now()->year);

        $cuentas = StudentAccount::with('student')
            ->where('year', $year)
            ->get();

        $totalDeuda = $cuentas->sum('total_debt');
        $totalPagado = $cuentas->sum('total_paid');
        $totalPendiente = $totalDeuda - $totalPagado;

        $enMora = $cuentas->where('status', StudentAccount::STATUS_MORA)->count();
        $alDia = $cuentas->where('status', StudentAccount::STATUS_AL_DIA)->count();
        $pendientes = $cuentas->where('status', StudentAccount::STATUS_PENDIENTE)->count();

        return response()->json([
            'year' => $year,
            'total_estudiantes' => $cuentas->count(),
            'resumen_financiero' => [
                'total_deuda' => $totalDeuda,
                'total_pagado' => $totalPagado,
                'total_pendiente' => $totalPendiente,
                'porcentaje_recaudado' => $totalDeuda > 0 ? round(($totalPagado / $totalDeuda) * 100, 2) : 0
            ],
            'resumen_estados' => [
                'en_mora' => $enMora,
                'al_dia' => $alDia,
                'pendientes' => $pendientes
            ],
            'cuentas' => $cuentas->map(function($cuenta) {
                return [
                    'student_id' => $cuenta->student_id,
                    'student_name' => $cuenta->student->nombres . ' ' . $cuenta->student->apellidos,
                    'total_debt' => $cuenta->total_debt,
                    'total_paid' => $cuenta->total_paid,
                    'saldo_pendiente' => $cuenta->saldo_pendiente,
                    'status' => $cuenta->status
                ];
            })
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/fees/reports/central",
     *     summary="Reporte central (PDF)",
     *     description="Genera PDF del consolidado financiero del año.",
     *     operationId="financeCentralReport",
     *     tags={"Finance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="year", in="query", required=false, description="Año (YYYY)", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="PDF generado"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function centralReport(Request $request)
    {
        $this->authorize('viewAny', StudentAccount::class);

        $year = $request->input('year', now()->year);

        $cuentas = StudentAccount::with(['student', 'accountCharges'])
            ->where('year', $year)
            ->get();

        $pdf = Pdf::loadView('finance.consolidado', [
            'cuentas' => $cuentas,
            'year' => $year
        ]);

        return $pdf->download('consolidado_financiero_' . $year . '.pdf');
    }

    /**
     * @OA\Get(
     *     path="/api/finance/charges/pending",
     *     summary="Cargos pendientes",
     *     description="Lista cargos pendientes del año.",
     *     operationId="chargesPending",
     *     tags={"Finance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="year", in="query", required=false, description="Año (YYYY)", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Listado devuelto"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function cargosPendientes(Request $request)
    {
        $this->authorize('viewAny', AccountCharge::class);

        $year = $request->input('year', now()->year);

        $cargos = AccountCharge::with(['studentAccount.student'])
            ->whereHas('studentAccount', function($q) use ($year) {
                $q->where('year', $year);
            })
            ->pendientes()
            ->orderBy('due_date', 'asc')
            ->paginate(50);

        return response()->json($cargos);
    }

    /**
     * @OA\Get(
     *     path="/api/finance/charges/overdue",
     *     summary="Cargos vencidos",
     *     description="Lista cargos vencidos del año.",
     *     operationId="chargesOverdue",
     *     tags={"Finance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="year", in="query", required=false, description="Año (YYYY)", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Listado devuelto"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function cargosVencidos(Request $request)
    {
        $this->authorize('viewAny', AccountCharge::class);

        $year = $request->input('year', now()->year);

        $cargos = AccountCharge::with(['studentAccount.student'])
            ->whereHas('studentAccount', function($q) use ($year) {
                $q->where('year', $year);
            })
            ->vencidos()
            ->orderBy('due_date', 'asc')
            ->paginate(50);

        return response()->json($cargos);
    }

    /**
     * @OA\Post(
     *     path="/api/fees/generate",
     *     summary="Generar cargos masivos",
     *     description="Genera cargos de matrícula y pensiones para todas las cuentas del año indicado.",
     *     operationId="generateFees",
     *     tags={"Finance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"year"},
     *             @OA\Property(property="year", type="integer", example=2025),
     *             @OA\Property(property="nivel", type="string", nullable=true, enum={"inicial","primaria","secundaria"}),
     *             @OA\Property(property="preview", type="boolean", nullable=true, example=false)
     *         )
     *     ),
     *     @OA\Response(response=202, description="Proceso de generación iniciado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
public function generateFees(Request $request)
{
    $this->authorize('create', AccountCharge::class);

    $data = $request->validate([
        'year'         => 'required|integer',
        'nivel'        => 'nullable|in:inicial,primaria,secundaria',
        'grado'        => 'nullable|integer',
        'paralelo'     => 'nullable|string|max:10',
        'mes'          => 'nullable|string|max:2',
        'fee_config_id'=> 'required|exists:fee_configs,id',
        'preview'      => 'boolean'
    ]);

    $year = (int) $data['year'];

    // 1) Traer la configuración de cobro
    $feeConfig = FeeConfig::findOrFail($data['fee_config_id']);

    // 2) Seleccionar estudiantes según filtros
    $studentsQuery = Student::query()
        ->where('nivel', $data['nivel'] ?? $feeConfig->nivel);

    if (!empty($data['paralelo'])) {
        $studentsQuery->where('paralelo', $data['paralelo']);
    }

    $students = $studentsQuery->get();

    if ($students->isEmpty()) {
        return response()->json([
            'message' => 'No se encontraron estudiantes para los filtros indicados',
            'filters' => $data
        ], 404);
    }

    // Si solo es vista previa, no guardamos nada
    if (!empty($data['preview'])) {
        return response()->json([
            'message'             => 'Vista previa de generación de cargos',
            'year'                => $year,
            'nivel'               => $data['nivel'] ?? $feeConfig->nivel,
            'total_estudiantes'   => $students->count(),
            'monto_por_estudiante'=> $feeConfig->monto ?? null,
            'monto_total_teorico' => ($feeConfig->monto ?? 0) * $students->count(),
        ]);
    }

    $creados  = 0;
    $omitidos = 0;

    DB::beginTransaction();

    try {
        foreach ($students as $student) {
            // 3) Crear/obtener cuenta del estudiante para ese año
            $account = StudentAccount::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'year'       => $year,
                ],
                [
                    'total_debt' => 0,
                    'total_paid' => 0,
                    // si tu tabla tiene 'status' y ENUM, ajusta aquí
                    // 'status'   => StudentAccount::STATUS_PENDIENTE ?? 'PENDIENTE',
                ]
            );

            // --- Preparar datos del cargo ---

            // Mes (tinyint) para account_charges.month
            $month = $data['mes']
                ?? ($feeConfig->mes ?? now()->format('m'));
            $month = (int) $month;

            // Concept enum: MATRICULA | MENSUALIDAD | OTRO
            $concept = 'OTRO';
            $conceptoTexto = mb_strtolower($feeConfig->concepto ?? '', 'UTF-8');

            if (str_contains($conceptoTexto, 'matr')) {
                $concept = 'MATRICULA';
            } elseif (str_contains($conceptoTexto, 'mensual') || str_contains($conceptoTexto, 'pensión') || str_contains($conceptoTexto, 'pension')) {
                $concept = 'MENSUALIDAD';
            }

            // Descripción libre (requerido por la tabla)
            $description = $feeConfig->concepto ?? 'Cargo automático';

            // Fecha de vencimiento
            if (!empty($feeConfig->vence_el)) {
                $dueDate = $feeConfig->vence_el;
            } else {
                $dueDate = Carbon::createFromDate($year, $month, 1)
                    ->endOfMonth()
                    ->toDateString();
            }

            // Usuario creador
            $createdBy = auth()->id() ?? 1; // fallback si no hay auth

            // 4) Crear cargo si no existe uno igual (por cuenta + concept + month + year)
            $charge = AccountCharge::firstOrCreate(
                [
                    'student_account_id' => $account->id,
                    'concept'            => $concept,
                    'month'              => $month,
                    'year'               => $year,
                ],
                [
                    'description' => $description,
                    'amount'      => $feeConfig->monto ?? 0,
                    'paid_amount' => 0,
                    'due_date'    => $dueDate,
                    'status'      => AccountCharge::STATUS_PENDING,
                    'created_by'  => $createdBy,
                ]
            );

            if ($charge->wasRecentlyCreated) {
                $creados++;
            } else {
                $omitidos++;
            }

            // 5) Recalcular totales de la cuenta
            $account->total_debt = AccountCharge::where('student_account_id', $account->id)->sum('amount');
            $account->total_paid = AccountCharge::where('student_account_id', $account->id)->sum('paid_amount');
            $account->save();
        }

        DB::commit();

        return response()->json([
            'message'            => 'Cargos generados correctamente',
            'year'               => $year,
            'nivel'              => $data['nivel'] ?? $feeConfig->nivel,
            'total_estudiantes'  => $students->count(),
            'charges_created'    => $creados,
            'charges_skipped'    => $omitidos,
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Error al generar cargos',
            'error'   => $e->getMessage(),
        ], 500);
    }
}
    public function cancel(Payment $payment)
    {
        // Opcional: si ya está cancelado, no hacer nada
        if ($payment->status === 'CANCELLED') {
            return response()->json([
                'message' => 'El pago ya estaba cancelado',
                'payment' => $payment,
            ], 200);
        }

        DB::beginTransaction();

        try {
            $account = $payment->studentAccount;

            // Revertir distribución: restar de cada cargo
            foreach ($payment->items as $item) {
                $cargo = $item->charge;

                // devolver el monto al cargo
                $nuevoPaid = max(0, $cargo->paid_amount - $item->amount);
                $cargo->paid_amount = $nuevoPaid;

                if ($nuevoPaid <= 0) {
                    $cargo->status = 'PENDING';
                } elseif ($nuevoPaid < $cargo->amount) {
                    $cargo->status = 'PARTIAL';
                } else {
                    $cargo->status = 'PAID';
                }

                $cargo->save();
            }

            // Ajustar total_paid de la cuenta
            $account->total_paid = max(0, $account->total_paid - $payment->total_amount);
            $account->save();

            // Marcar el pago como cancelado (asegúrate de tener columna status en payments)
            $payment->status = 'CANCELLED';
            $payment->save();

            DB::commit();

            return response()->json([
                'message' => 'Pago cancelado correctamente',
                'payment' => $payment->load('items.charge', 'studentAccount'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al cancelar el pago',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

}
