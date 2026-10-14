<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'fee_config_id', 'monto', 'estado', 'fecha_vencimiento', 'fecha_pago',
        'monto_pagado', 'metodo_pago', 'observaciones', 'registrado_por'
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'fecha_pago' => 'date',
        'monto' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
    ];

    public function student() {
        return $this->belongsTo(Student::class);
    }

    public function feeConfig() {
        return $this->belongsTo(FeeConfig::class);
    }

    public function registradoPor() {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
