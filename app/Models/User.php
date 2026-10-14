<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

        protected $fillable = [
            'name', 'email', 'password',
            'username', 'role', 'must_change_password',
            'teacher_id', 'student_id'
        ];

        protected $hidden = ['password','remember_token'];

        protected $casts = [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];

        public function teacher() { return $this->belongsTo(Teacher::class, 'teacher_id'); }
        public function student() { return $this->belongsTo(Student::class, 'student_id'); }

        public function assignments()
    {
        return $this->hasMany(Assignment::class, 'teacher_id');
    }

    public function isTeacher(): bool { return $this->role === 'teacher'; }
        public function isStudent(): bool { return $this->role === 'student'; }
}

