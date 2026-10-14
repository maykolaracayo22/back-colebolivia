<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Enrollment",
 *     type="object",
 *     required={"year","student_id","section_id"},
 *     @OA\Property(property="id", type="integer", example=10),
 *     @OA\Property(property="year", type="string", example="2025"),
 *     @OA\Property(property="student_id", type="integer", example=123),
 *     @OA\Property(property="section_id", type="integer", example=45),
 *     @OA\Property(property="estado", type="string", enum={"PREINSCRITO","INSCRITO","BAJA_TEMP","BAJA_DEF"}, example="INSCRITO"),
 *     @OA\Property(property="fecha", type="string", format="date", nullable=true, example="2025-02-10"),
 *     @OA\Property(property="student", ref="#/components/schemas/Student"),
 *     @OA\Property(property="section", ref="#/components/schemas/Section"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Enrollment extends Model {
    use HasFactory;

    protected $fillable = ['year','student_id','section_id','estado','fecha'];

    public function student() { return $this->belongsTo(Student::class); }
    public function section() { return $this->belongsTo(Section::class); }
protected static function boot()
{
    parent::boot();

    static::created(function ($enrollment) {
        if (empty($enrollment->code)) {
            $enrollment->forceFill([
                'code' => 'MTR-' . $enrollment->year . '-' . str_pad((string) $enrollment->id, 4, '0', STR_PAD_LEFT),
            ])->saveQuietly();
        }
    });
}

}
