<?php

namespace App\Services;

use App\Models\Acta;
use App\Models\ActaNote;
use App\Models\ActaTermTotal;
use App\Models\CentralizadorLine;
use Illuminate\Support\Facades\DB;

class GradeService
{
    /**
     * Recompute aggregates for an Acta: per-student TSPE and TN, upsert acta_term_totals
     * and update centralizador_lines.t{term}.
     */
    public function recomputeActa(Acta $acta): void
    {
        DB::transaction(function() use ($acta) {
            $term = $acta->term; // expected like 'T1','T2','T3'

            // Aggregate numeric notes by student using a single query
            $rows = DB::table('acta_notes')
                ->where('acta_notes.acta_id', $acta->id)
                ->whereNotNull('acta_notes.valor')
                ->where('acta_notes.tipo', 'NUM')
                ->groupBy('acta_notes.student_id')
                ->select('acta_notes.student_id', DB::raw('SUM(acta_notes.valor) as suma'))
                ->get();

            $byStudent = [];
            $studentIds = [];
            foreach ($rows as $r) {
                $sid = (int)$r->student_id;
                $val = (int)$r->suma;
                $byStudent[$sid] = $val;
                $studentIds[$sid] = $sid;
            }

            // include students that have term totals (auto/pe) even if no notes
            $termTotalsExisting = ActaTermTotal::where('acta_id', $acta->id)->get();
            foreach ($termTotalsExisting as $tt) {
                $studentIds[$tt->student_id] = $tt->student_id;
            }

            if (empty($studentIds)) return;

            // fetch existing term totals indexed by student
            $termTotalsMap = ActaTermTotal::where('acta_id', $acta->id)
                ->whereIn('student_id', array_values($studentIds))
                ->get()->keyBy('student_id');

            foreach ($studentIds as $studentId) {
                $studentId = (int)$studentId;

                $sumNotas = $byStudent[$studentId] ?? 0;

                $termTotal = $termTotalsMap[$studentId] ?? new ActaTermTotal(['acta_id' => $acta->id, 'student_id' => $studentId]);

                $auto = (int) ($termTotal->autoevaluacion ?? 0);
                $pe = is_numeric($termTotal->pe) ? (int)$termTotal->pe : 0;

                $tspe = $sumNotas + $auto;
                $tn = $tspe + $pe;

                // persist term total
                ActaTermTotal::updateOrCreate(
                    ['acta_id' => $acta->id, 'student_id' => $studentId],
                    ['autoevaluacion' => $auto, 'pe' => $pe, 'tspe' => $tspe, 'tn' => $tn]
                );

                // update centralizador
                $this->updateCentralizadorLine($acta, $studentId, $term, $tn, $acta->status === 'PUBLISHED');
            }
        });
    }

    protected function updateCentralizadorLine(Acta $acta, int $studentId, $term, int $tn, bool $publish=false): void
    {
        // map term name to column t1/t2/t3
        $col = match(strtoupper($term)) {
            'T1' => 't1',
            'T2' => 't2',
            'T3' => 't3',
            default => 't1'
        };

        $line = CentralizadorLine::firstOrNew([
            'year' => $acta->year,
            'grado' => $acta->grado,
            'seccion' => $acta->seccion,
            'materia_id' => $acta->materia_id,
            'student_id' => $studentId,
        ]);

        // ensure basic fields
        $line->nivel = $acta->nivel;
        $line->grado = $acta->grado;
        $line->seccion = $acta->seccion;

        $line->{$col} = $tn;

        // if acta is published, mark centralizador line as published
        if ($publish) {
            $line->status = 'PUBLISHED';
            $line->published_at = $acta->published_at ?? now();
            $line->published_by = $acta->published_by ?? auth()->id();
        }

        // compute PA if t1,t2,t3 present
        $t1 = $line->t1; $t2 = $line->t2; $t3 = $line->t3;
        $vals = array_filter([$t1,$t2,$t3], fn($v) => !is_null($v));
        if (count($vals) === 3) {
            $line->pa = (int) round(array_sum($vals) / 3);
        }

        $line->save();
    }
}
