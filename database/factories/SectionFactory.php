<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Section;

class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition(): array
    {
        $niv = $this->faker->randomElement(['inicial','primaria','secundaria']);
        return [
            'year' => date('Y'),
            'nivel' => $niv,
            'curso' => $this->faker->randomElement(['1ro','2do','3ro','4to','5to','6to']),
            'paralelo' => $this->faker->randomElement(['A','B','C']),
            'turno' => $this->faker->randomElement(['Mañana','Tarde','Noche']),
            'aula' => $this->faker->optional()->bothify('Aula-##'),
            'capacidad' => $this->faker->numberBetween(20,40),
            'tutor_id' => null,
            'tutor_nombre' => null,
            'estado' => 'ACTIVA',
        ];
    }
}
