<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Acta;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\ActaEvaluation;
use App\Models\ActaNote;
use App\Models\ActaTermTotal;
use App\Models\CentralizadorLine;
use App\Services\GradeService;

class GradeServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function recompute_acta_updates_term_totals_and_centralizador()
    {
        // Create minimal related data
        $subject = Subject::factory()->create(['nombre'=>'Matemáticas']);
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->create();

        $acta = Acta::create([
            'year' => 2025,
            'nivel' => 'primaria',
            'grado' => '2',
            'seccion' => 'A',
            'ref' => 'TEST-1',
            'materia_id' => $subject->id,
            'materia_nombre' => $subject->nombre,
            'docente_id' => $teacher->id,
            'docente_nombre' => 'Prof X',
            'term' => 'T1',
            'status' => 'DRAFT'
        ]);

        // evaluations (two simple evaluations) and notes for student
        $e1 = ActaEvaluation::create(['acta_id'=>$acta->id,'nombre'=>'Eval 1','peso'=>0]);
        $e2 = ActaEvaluation::create(['acta_id'=>$acta->id,'nombre'=>'Eval 2','peso'=>0]);

        // notes for student (values 2 and 3)
        ActaNote::create(['acta_id'=>$acta->id,'student_id'=>$student->id,'evaluation_id'=>$e1->id,'valor'=>2,'tipo'=>'NUM']);
        ActaNote::create(['acta_id'=>$acta->id,'student_id'=>$student->id,'evaluation_id'=>$e2->id,'valor'=>3,'tipo'=>'NUM']);

        // auto and pe
        ActaTermTotal::create(['acta_id'=>$acta->id,'student_id'=>$student->id,'autoevaluacion'=>1,'pe'=>5]);

        // Run recompute
        $svc = new GradeService();
        $svc->recomputeActa($acta);

        $tt = ActaTermTotal::where('acta_id',$acta->id)->where('student_id',$student->id)->first();
        $this->assertNotNull($tt);

        // tspe = SER(2)+SABER(3)+auto(1) = 6
        $this->assertEquals(6, $tt->tspe);
        // tn = tspe + pe = 6 + 5 = 11
        $this->assertEquals(11, $tt->tn);

        $line = CentralizadorLine::where('year',$acta->year)->where('materia_id',$acta->materia_id)->where('student_id',$student->id)->first();
        $this->assertNotNull($line);
        $this->assertEquals(11, $line->t1);
    }
}
