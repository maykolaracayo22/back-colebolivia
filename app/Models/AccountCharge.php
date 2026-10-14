<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountCharge extends Model
{
    use HasFactory;

    // Estados de cargos
    public const STATUS_PENDING  = 'PENDING';
    public const STATUS_PARTIAL  = 'PARTIAL';
    public const STATUS_PAID     = 'PAID';
    public const STATUS_CANCELED = 'CANCELED';

    protected $fillable = [
        'student_account_id',
        'concept',      // MATRICULA | MENSUALIDAD | OTRO
        'month',        // tinyInteger
        'year',         // year
        'description',  // string
        'amount',
        'paid_amount',
        'due_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'month'       => 'integer',
        'year'        => 'integer',
        'amount'      => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date'    => 'date',
    ];

    /**
     * Relación: cargo pertenece a una cuenta financiera
     */
    public function studentAccount()
    {
        return $this->belongsTo(StudentAccount::class);
    }

    /**
     * Relación: usuario que creó el cargo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación: items de pago asociados a este cargo (si tienes PaymentItem)
     */
    public function paymentItems()
    {
        return $this->hasMany(PaymentItem::class, 'charge_id');
    }

    /**
     * Scope: cargos pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: cargos vencidos
     */
    public function scopeVencidos($query)
    {
        return $query
            ->where('status', self::STATUS_PENDING)
            ->whereDate('due_date', '<', now());
    }
}
