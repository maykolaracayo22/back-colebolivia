<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Acta;
use Illuminate\Support\Str;

class ActaFactory extends Factory
{
    protected $model = Acta::class;

    public function definition(): array
    {
        $year = date('Y');
        $term = $this->faker->randomElement(['T1','T2','T3']);
        $ref = sprintf('%s:%s:%s:%s', $year, 'SEC1', $this->faker->numberBetween(1,10), $term);
        return [
            'ref' => $ref,
            'year' => $year,
            'nivel' => $this->faker->randomElement(['inicial','primaria','secundaria']),
            'grado' => $this->faker->randomElement(['1ro','2do','3ro','4to','5to','6to']),
            'section_id' => null,
            'term' => $term,
            'subject_id' => null,
            'subject_nombre' => null,
            'teacher_id' => null,
            'teacher_nombre' => null,
            'status' => 'DRAFT',
            'etag' => Str::uuid()->toString(),
            'metrics' => null,
        ];
    }
}
