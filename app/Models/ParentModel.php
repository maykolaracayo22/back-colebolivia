<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Padre/Madre/Tutor de familia
 */
class ParentModel extends Model
{
    use HasFactory;

    protected $table = 'parents';

    protected $fillable = [
        'user_id',
        'ci',
        'phone',
        'address',
        'ocupacion',
        'lugar_trabajo',
    ];

    protected $appends = ['nombre_completo'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id')
            ->withTimestamps()
            ->withPivot('relationship');
    }

    public function getNombreCompletoAttribute()
    {
        return $this->user ? $this->user->name : 'Sin nombre';
    }
}
