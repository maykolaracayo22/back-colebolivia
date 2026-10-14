<?php

namespace App\Http\Controllers;

use App\Models\StudentFee;
use App\Models\StudentAccount;
use App\Models\AccountCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentFeeController extends Controller
{
    /**
     * Lista todas las deudas registradas por estudiante
     */
    public function index(Request $request)
    {
        $student_id = $request->query('student_id');

        $fees = StudentFee::with(['student', 'feeConfig'])
            ->when($student_id, fn($q) => $q->where('student_id', $student_id))
            ->orderBy('created_at', 'DESC')
            ->get();

        return response()->json($fees);
    }

    /**
     * Registrar deuda y generar cargo contable automáticamente
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'     => 'required|exists:students,id',
            'fee_config_id'  => 'required|exists:fee_configs,id',
            'amount'         => 'required|numeric|min:1',
            'due_date'       => 'required|date',
            'payment_method' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // 1️⃣ Crear la deuda conceptual (StudentFee)
            $fee = StudentFee::create($data);

            // 2️⃣ Obtener o crear Cuenta del Estudiante para ese año
            $year = now()->year;
            $account = StudentAccount::firstOrCreate(
                ['student_id' => $fee->student_id, 'year' => $year],
                ['total_debt' => 0, 'total_paid' => 0, 'status' => 'PENDIENTE']
            );

            // 3️⃣ Crear el cargo contable que el PaymentService consumirá
            $charge = AccountCharge::create([
                'student_account_id' => $account->id,
                'fee_id'             => $fee->id,
                'concept'            => 'Pensión / ' . $year,
                'month'              => now()->month,
                'year'               => $year,
                'amount'             => $fee->amount,
                'paid_amount'        => 0,
                'status'             => 'PENDING',
                'due_date'           => $fee->due_date,
            ]);

            // 4️⃣ Actualizar deuda total en la cuenta
            $account->increment('total_debt', $fee->amount);

            DB::commit();

            return response()->json([
                'message' => 'Deuda registrada correctamente',
                'fee'     => $fee,
                'charge'  => $charge,
                'account' => $account,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar deuda',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar una deuda específica
     */
    public function show(StudentFee $studentFee)
    {
        return response()->json($studentFee->load(['student', 'feeConfig']));
    }

    /**
     * Actualizar deuda
     */
    public function update(Request $request, StudentFee $studentFee)
    {
        $data = $request->validate([
            'amount'   => 'sometimes|numeric|min:1',
            'due_date' => 'sometimes|date',
        ]);

        $studentFee->update($data);

        return response()->json([
            'message' => 'Actualizado correctamente',
            'fee'     => $studentFee,
        ]);
    }

    /**
     * Eliminar deuda
     */
    public function destroy(StudentFee $studentFee)
    {
        $studentFee->delete();
        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
