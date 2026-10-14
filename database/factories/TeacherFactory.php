<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Teacher;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'ci' => $this->faker->optional()->numerify('########'),
            'nombres' => $this->faker->firstName(),
            'apellidos' => $this->faker->lastName(),
            'email' => $this->faker->optional()->safeEmail(),
            'telefono' => $this->faker->optional()->phoneNumber(),
            'rda_numero' => 'RDA-'.$this->faker->numerify('#####'),
            'rda_estado' => $this->faker->randomElement(['VIGENTE','OBSERVADO','VENCIDO']),
            'rda_vencimiento' => $this->faker->optional()->date(),
            'activo' => true,
        ];
    }
}
