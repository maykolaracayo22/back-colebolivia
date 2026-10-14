<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Student;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'rude' => null,
            'ci' => $this->faker->optional()->numerify('########'),
            'nombres' => $this->faker->firstName(),
            'apellidos' => $this->faker->lastName(),
            'sexo' => $this->faker->randomElement(['M','F']),
            'fecha_nac' => $this->faker->optional()->date('Y-m-d', '2015-01-01'),
            'email' => $this->faker->optional()->safeEmail(),
            'telefono' => $this->faker->optional()->phoneNumber(),
            'activo' => true,
            'nivel' => $this->faker->randomElement(['inicial','primaria','secundaria']),
            'curso' => $this->faker->randomElement(['1ro','2do','3ro','4to','5to','6to']),
            'paralelo' => $this->faker->randomElement(['A','B','C']),
            'codigo' => 'EST-'.date('Y').'-'.str_pad($this->faker->unique()->numberBetween(1,999), 3, '0', STR_PAD_LEFT),
            'usuario' => strtolower($this->faker->firstName()).'.'.strtolower($this->faker->lastName()),
            'password_temp' => 'temp123',
            'anio_ingreso' => date('Y'),
            'mes_ingreso' => str_pad($this->faker->numberBetween(1,12), 2, '0', STR_PAD_LEFT),
        ];
    }
}

