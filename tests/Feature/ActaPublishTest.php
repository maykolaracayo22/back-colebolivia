<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Acta;
use App\Models\ActaEvaluation;
use App\Models\ActaNote;
use App\Models\ActaTermTotal;
use Spatie\Permission\Models\Role;
use Laravel\Sanctum\Sanctum;

class ActaPublishTest extends TestCase
{
    use RefreshDatabase;

    public function test_publish_updates_centralizador_and_sets_published()
    {
        // create roles
        Role::create(['name' => 'teacher']);
        Role::create(['name' => 'director_academico']);

        // create teacher user and director user
        $teacherUser = User::factory()->create();
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
        $teacherUser->assignRole('teacher');

        $director = User::factory()->create();
        $director->assignRole('director_academico');

        $student = Student::factory()->create();
        $subject = Subject::factory()->create(['nombre' => 'Matemáticas']);

        $acta = Acta::create([
            'year' => 2025,
            'nivel' => 'primaria',
            'grado' => '2',
            'seccion' => 'A',
            'ref' => 'TST2',
            'materia_id' => $subject->id,
            'materia_nombre' => $subject->nombre,
            'docente_id' => $teacher->id,
            'docente_nombre' => 'Prof X',
            'term' => 'T1',
            'status' => 'LOCKED',
        ]);

        $eval1 = ActaEvaluation::create(['acta_id' => $acta->id, 'nombre' => 'P1', 'peso' => 0]);

        // create notes and term totals
        ActaNote::create(['acta_id' => $acta->id, 'student_id' => $student->id, 'evaluation_id' => $eval1->id, 'valor' => 7, 'tipo' => 'NUM']);
        ActaTermTotal::create(['acta_id' => $acta->id, 'student_id' => $student->id, 'autoevaluacion' => 1, 'pe' => 4]);

        // director publishes
        Sanctum::actingAs($director);
        $resp = $this->postJson("/api/actas/{$acta->id}/publish");
        $resp->assertStatus(200);

        $this->assertDatabaseHas('actas', ['id' => $acta->id, 'status' => 'PUBLISHED']);

        // centralizador must have t1 = notes7 + auto1 + pe4 = 12
        $this->assertDatabaseHas('centralizador_lines', ['year' => 2025, 'materia_id' => $subject->id, 'student_id' => $student->id, 't1' => 12]);
    }
}
