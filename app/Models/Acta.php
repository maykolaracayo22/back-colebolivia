<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Acta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'actas';

    protected $fillable = [
        'ref',
        'year',
        'nivel',
        'grado',
        'seccion',
        'materia_id',
        'materia_nombre',
        'docente_id',
        'docente_nombre',
        'term',
        'status',
        'window_starts_at',
        'window_ends_at',
        'metrics',
        'etag',
        'locked_at',
        'locked_by',
        'published_at',
        'published_by',
    ];

    protected $casts = [
        'window_starts_at' => 'datetime',
        'window_ends_at'   => 'datetime',
        'locked_at'        => 'datetime',
        'published_at'     => 'datetime',
        'metrics'          => 'array',
    ];

    public function criteria()
    {
        return $this->hasMany(ActaCriteria::class, 'acta_id');
    }

    public function notes()
    {
        return $this->hasMany(ActaNote::class, 'acta_id');
    }

    public function termTotals()
    {
        return $this->hasMany(ActaTermTotal::class, 'acta_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'materia_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'docente_id');
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function publishedBy()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'DRAFT');
    }

    public function scopeLocked($query)
    {
        return $query->where('status', 'LOCKED');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'PUBLISHED');
    }

    public function scopeByDocente($query, $id)
    {
        return $query->where('docente_id', $id);
    }

    public function isDraft()
    {
        return $this->status === 'DRAFT';
    }

    public function isLocked()
    {
        return $this->status === 'LOCKED';
    }

    public function isPublished()
    {
        return $this->status === 'PUBLISHED';
    }

    public function isWithinWindow()
    {
        $now = now();
        return $now->between($this->window_starts_at, $this->window_ends_at);
    }

    public function canEdit()
    {
        return $this->isDraft() && $this->isWithinWindow();
    }
        public function evaluaciones()
    {
        return $this->hasMany(ActaEvaluation::class, 'acta_id');
    }
    public function evaluations()
    {
        return $this->hasMany(ActaEvaluation::class, 'acta_id');
    }
}
