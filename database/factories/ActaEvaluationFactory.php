<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ActaEvaluation;

class ActaEvaluationFactory extends Factory
{
    protected $model = ActaEvaluation::class;

    public function definition(): array
    {
        return [
            'acta_id' => null,
            'nombre' => $this->faker->word(),
            'peso' => $this->faker->numberBetween(10,50),
            'fecha' => $this->faker->optional()->date(),
        ];
    }
}
