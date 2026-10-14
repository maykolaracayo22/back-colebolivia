<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\StudentAccount;
use App\Models\AccountCharge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Pagos - Distribución FIFO
 */
class PaymentService
{
    /**
     * Distribuye un pago entre los cargos pendientes usando FIFO
     */
    public function distribuirPago(Payment $payment): array
    {
        Log::info('💳 Iniciando distribución FIFO de pago', [
            'payment_id' => $payment->id,
            'monto'      => $payment->total_amount,
        ]);

        DB::beginTransaction();

        try {
            $studentAccount  = $payment->studentAccount;
            $montoDisponible = $payment->total_amount;
            $distribucion    = [];

            $cargosPendientes = AccountCharge::where('student_account_id', $studentAccount->id)
                ->whereIn('status', ['PENDING', 'PARTIAL'])
                ->orderBy('due_date', 'ASC')
                ->orderBy('id', 'ASC')
                ->get();

            foreach ($cargosPendientes as $cargo) {
                if ($montoDisponible <= 0) {
                    break;
                }

                $montoPendiente = $cargo->amount - $cargo->paid_amount;

                if ($montoPendiente <= 0) {
                    continue;
                }

                $montoAplicado = min($montoDisponible, $montoPendiente);

                PaymentItem::create([
                    'payment_id' => $payment->id,
                    'charge_id'  => $cargo->id,
                    'amount'     => $montoAplicado,
                ]);

                $nuevoPaidAmount = $cargo->paid_amount + $montoAplicado;
                $cargo->update([
                    'paid_amount' => $nuevoPaidAmount,
                    'status'      => $nuevoPaidAmount >= $cargo->amount ? 'PAID' : 'PARTIAL',
                ]);

                $distribucion[] = [
                    'cargo_id'       => $cargo->id,
                    'concepto'       => $cargo->concept,
                    'mes'            => $cargo->month,
                    'monto_aplicado' => $montoAplicado,
                    'estado_final'   => $cargo->status,
                ];

                $montoDisponible -= $montoAplicado;

                Log::info("  ↳ {$cargo->concept} {$cargo->month}/{$cargo->year}: {$montoAplicado} Bs", [
                    'cargo_id' => $cargo->id,
                    'estado'   => $cargo->status,
                ]);
            }

            $nuevoTotalPaid = $studentAccount->total_paid + $payment->total_amount;
            $studentAccount->update(['total_paid' => $nuevoTotalPaid]);

            $this->actualizarEstadoCuenta($studentAccount);

            DB::commit();

            Log::info('✅ Distribución completada', [
                'items_creados'  => count($distribucion),
                'monto_sobrante' => $montoDisponible,
            ]);

            return [
                'success'            => true,
                'payment_id'         => $payment->id,
                'monto_total'        => $payment->total_amount,
                'monto_distribuido'  => $payment->total_amount - $montoDisponible,
                'monto_sobrante'     => $montoDisponible,
                'distribucion'       => $distribucion,
                // 👇 alias para tu controller
                'items_aplicados'    => $distribucion,
                'nuevo_total_pagado' => $nuevoTotalPaid,
                'estado_cuenta'      => $studentAccount->status,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Error en distribución de pago', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Registra un pago nuevo y lo distribuye automáticamente
     */
    public function registrarPago(
        StudentAccount $studentAccount,
        float $monto,
        string $metodo,
        ?string $referencia = null,
        ?int $userId = null
    ): array {
        Log::info('💰 Registrando nuevo pago', [
            'student_account_id' => $studentAccount->id,
            'monto'              => $monto,
            'metodo'             => $metodo,
        ]);

        DB::beginTransaction();

        try {
            $payment = Payment::create([
                'student_account_id' => $studentAccount->id,
                'total_amount'       => $monto,
                'method'             => $metodo,
                'reference'          => $referencia,
                'payment_date'       => now(),
                'received_by'        => $userId ?? auth()->id(),
            ]);

            $resultado = $this->distribuirPago($payment);

            // recargar relaciones para devolver el pago “bonito”
            $payment->load([
                'studentAccount',
                'items.charge',
            ]);

            DB::commit();

            return array_merge($resultado, [
                'payment'       => $payment,
                'recibo_numero' => $payment->recibo_numero ?? null,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function actualizarEstadoCuenta(StudentAccount $studentAccount): void
    {
        $deudaPendiente = $studentAccount->total_debt - $studentAccount->total_paid;

        if ($deudaPendiente <= 0) {
            $studentAccount->update(['status' => 'AL_DIA']);
        } else {
            $hayVencidos = AccountCharge::where('student_account_id', $studentAccount->id)
                ->whereIn('status', ['PENDING', 'PARTIAL'])
                ->where('due_date', '<', now())
                ->exists();

            $studentAccount->update([
                'status' => $hayVencidos ? 'MORA' : 'PENDIENTE',
            ]);
        }
    }

    public function estadoCuenta(StudentAccount $studentAccount): array
    {
        $cargos = AccountCharge::where('student_account_id', $studentAccount->id)
            ->orderBy('due_date', 'ASC')
            ->get();

        $pagos = Payment::where('student_account_id', $studentAccount->id)
            ->orderBy('payment_date', 'DESC')
            ->get();

        return [
            'total_deuda'     => $studentAccount->total_debt,
            'total_pagado'    => $studentAccount->total_paid,
            'saldo_pendiente' => $studentAccount->total_debt - $studentAccount->total_paid,
            'estado'          => $studentAccount->status,
            'cargos'          => $cargos->map(function ($cargo) {
                return [
                    'id'         => $cargo->id,
                    'concepto'   => $cargo->concept,
                    'mes'        => $cargo->month,
                    'year'       => $cargo->year,
                    'monto'      => $cargo->amount,
                    'pagado'     => $cargo->paid_amount,
                    'pendiente'  => $cargo->amount - $cargo->paid_amount,
                    'vencimiento'=> $cargo->due_date,
                    'estado'     => $cargo->status,
                ];
            }),
            'pagos' => $pagos->map(function ($pago) {
                return [
                    'id'     => $pago->id,
                    'recibo' => $pago->recibo_numero ?? null,
                    'fecha'  => $pago->payment_date,
                    'monto'  => $pago->total_amount,
                    'metodo' => $pago->method,
                    'items'  => $pago->items->map(function ($item) {
                        return [
                            'cargo_id' => $item->charge_id,
                            'concepto' => $item->charge->concept,
                            'monto'    => $item->amount,
                        ];
                    }),
                ];
            }),
        ];
    }

    public function generarRecibo(Payment $payment): string
    {
        return "Recibo #{$payment->recibo_numero} generado";
    }
}
