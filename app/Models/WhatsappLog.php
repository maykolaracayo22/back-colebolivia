<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model {
    use HasFactory;

    protected $fillable = [
        'user_id', 'telefono', 'mensaje', 'tipo', 'estado', 'respuesta_api'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
