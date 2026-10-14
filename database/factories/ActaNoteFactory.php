<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ActaNote;

class ActaNoteFactory extends Factory
{
    protected $model = ActaNote::class;

    public function definition(): array
    {
        return [
            'acta_id' => null,
            'evaluation_id' => null,
            'student_id' => null,
            'tipo' => 'NUM',
            'valor' => $this->faker->randomFloat(2, 0, 100),
            'valor_qual' => null,
        ];
    }
}
