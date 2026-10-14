<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Assignment;

class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    public function definition(): array
    {
        return [
            'year' => date('Y'),
            'section_id' => null,
            'subject_id' => null,
            'teacher_id' => null,
            'dia' => $this->faker->randomElement(['Lun','Mar','Mié','Jue','Vie']),
            'inicio' => $this->faker->time('H:i:s'),
            'fin' => $this->faker->time('H:i:s'),
            'aula' => $this->faker->optional()->bothify('Aula-#'),
            'titular' => true,
        ];
    }
}
