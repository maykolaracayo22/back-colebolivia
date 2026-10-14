<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActaCriteria extends Model
{
    use HasFactory;

    protected $table = 'acta_criteria';
    protected $fillable = ['acta_id', 'categoria', 'code', 'label', 'max_points'];
    public $timestamps = false;

    public function acta()
    {
        return $this->belongsTo(Acta::class, 'acta_id');
    }

    public function notes()
    {
        return $this->hasMany(ActaNote::class, 'criterio_id');
    }
}
