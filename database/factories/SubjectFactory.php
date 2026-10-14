<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Subject;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->word(),
            'nivel' => $this->faker->randomElement(['inicial','primaria','secundaria']),
            'area' => $this->faker->optional()->word(),
        ];
    }
}
