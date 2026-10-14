<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'payment_date' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    // Métodos de pago
    const METHOD_EFECTIVO      = 'EFECTIVO';
    const METHOD_TRANSFERENCIA = 'TRANSFERENCIA';
    const METHOD_QR            = 'QR';
    const METHOD_TARJETA       = 'TARJETA';

    // Relaciones
    public function studentAccount(): BelongsTo
    {
        return $this->belongsTo(StudentAccount::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // Nombre "bonito" que ya usas en PaymentService
    public function items(): HasMany
    {
        return $this->hasMany(PaymentItem::class);
    }

    // Alias para compatibilidad con FinanceController (paymentItems)
    public function paymentItems(): HasMany
    {
        return $this->hasMany(PaymentItem::class); // misma relación
    }

    // Scopes
    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', $method);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('payment_date', $date);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    // Métodos de ayuda
    public function getMethodName(): string
    {
        return match ($this->method) {
            self::METHOD_EFECTIVO      => 'Efectivo',
            self::METHOD_TRANSFERENCIA => 'Transferencia Bancaria',
            self::METHOD_QR            => 'Código QR',
            self::METHOD_TARJETA       => 'Tarjeta de Crédito/Débito',
            default                    => 'Desconocido',
        };
    }

    public function getDistributionDetail(): array
    {
        return $this->items()
            ->with('charge')
            ->get()
            ->map(function ($item) {
                return [
                    'charge_id' => $item->charge_id,
                    'concept'   => $item->charge->getConceptName(),
                    'amount'    => $item->amount,
                ];
            })
            ->toArray();
    }
}
