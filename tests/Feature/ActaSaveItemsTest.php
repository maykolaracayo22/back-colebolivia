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
use Spatie\Permission\Models\Permission;
use Laravel\Sanctum\Sanctum;

class ActaSaveItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_items_creates_notes_and_term_totals_and_updates_centralizador()
    {
        // create roles
        $role = Role::create(['name' => 'teacher']);
        Permission::create(['name' => 'actas.edit']);
        Permission::create(['name' => 'notas.editar']);
        $role->givePermissionTo(['actas.edit','notas.editar']);

        // create user and teacher
        $user = User::factory()->create();
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);
        $user->assignRole('teacher');

        $student = Student::factory()->create();
        $subject = Subject::factory()->create(['nombre' => 'Matemáticas']);

        $acta = Acta::create([
            'year' => 2025,
            'nivel' => 'primaria',
            'grado' => '2',
            'seccion' => 'A',
            'ref' => 'TST1',
            'materia_id' => $subject->id,
            'materia_nombre' => $subject->nombre,
            'docente_id' => $teacher->id,
            'docente_nombre' => 'Prof X',
            'term' => 'T1',
            'status' => 'DRAFT',
        ]);

        $eval1 = ActaEvaluation::create(['acta_id' => $acta->id, 'nombre' => 'P1', 'peso' => 0]);

        $payload = [
            'notes' => [
                ['student_id' => $student->id, 'evaluation_id' => $eval1->id, 'valor' => 8]
            ],
            'auto' => [ ['student_id' => $student->id, 'valor' => 1] ],
            'pe' => [ ['student_id' => $student->id, 'valor' => 5] ],
        ];

        // Sanity: user has role and permission
        $this->assertTrue($user->hasRole('teacher'));
        $this->assertTrue($user->can('actas.edit'));

        Sanctum::actingAs($user);
        $resp = $this->putJson("/api/actas/{$acta->id}/items", $payload);

        // Dump response body for debugging
        fwrite(STDOUT, "RESPONSE: " . $resp->getContent() . PHP_EOL);

        $resp->assertStatus(200);

        $this->assertDatabaseHas('acta_notes', ['acta_id' => $acta->id, 'student_id' => $student->id, 'evaluation_id' => $eval1->id, 'valor' => 8]);
        $this->assertDatabaseHas('acta_term_totals', ['acta_id' => $acta->id, 'student_id' => $student->id, 'autoevaluacion' => 1, 'pe' => 5]);

        // centralizador line should be created and t1 = tspe+pe -> notes8 + auto1 + pe5 => tspe=9 tn=14
        $this->assertDatabaseHas('centralizador_lines', ['year' => 2025, 'materia_id' => $subject->id, 'student_id' => $student->id, 't1' => 14]);
    }
}
