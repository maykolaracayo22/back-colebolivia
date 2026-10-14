<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'year',
        'total_debt',
        'total_paid',
        'status'
    ];

    protected $casts = [
        'total_debt' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'year' => 'integer'
    ];

    const STATUS_AL_DIA = 'AL_DIA';
    const STATUS_PENDIENTE = 'PENDIENTE';
    const STATUS_MORA = 'MORA';

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function accountCharges()
    {
        return $this->hasMany(AccountCharge::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeEnMora($query)
    {
        return $query->where('status', self::STATUS_MORA);
    }

    public function scopeAlDia($query)
    {
        return $query->where('status', self::STATUS_AL_DIA);
    }

    public function getSaldoPendienteAttribute()
    {
        return $this->total_debt - $this->total_paid;
    }

    public function tieneMora()
    {
        return $this->accountCharges()
            ->whereIn('status', ['PENDING', 'PARTIAL'])
            ->where('due_date', '<', now())
            ->exists();
    }
}
