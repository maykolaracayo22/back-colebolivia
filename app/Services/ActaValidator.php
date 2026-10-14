<?php

namespace App\Services;

use App\Models\Acta;
use App\Models\ActaNote;

class ActaValidator
{
    public function validateActa(Acta $acta): array
    {
        $errores = [];
        $evaluaciones = $acta->evaluations()->get();
        $notes = $acta->notes()->get();

        // 1) Suma de pesos
        $sum = (float) $evaluaciones->sum('peso');
        $pesosOK = abs($sum - 100.0) < 0.0001;
        if (!$pesosOK) {
            $errores[] = "La suma de pesos es {$sum}, debe ser 100.";
        }

        // 2) Reglas por nivel
        $nivel = $acta->nivel ?: 'primaria';
        $cfg = config("grades.niveles.{$nivel}");
        $type = $cfg['type'] ?? 'NUMERIC';

        // 3) ValidaciÃ³n de notas
        $filled = 0;
        $totalCeldas = 0;

        foreach ($acta->students()->pluck('id') as $studentId) {
            foreach ($evaluaciones as $ev) {
                $totalCeldas++;
                /** @var ActaNote|null $n */
                $n = $notes->first(function ($x) use ($studentId, $ev, $acta) {
                    return $x->student_id === $studentId && $x->evaluation_id === $ev->id && $x->acta_id === $acta->id;
                });

                if (!$n) continue; // vacÃ­o permitido en DRAFT

                if ($n->tipo === 'NUM') {
                    if (!is_null($n->valor)) {
                        $filled++;
                        $min = $cfg['num']['minNota'] ?? 0;
                        $max = $cfg['num']['maxNota'] ?? 100;
                        $dec = (int)($cfg['num']['decimales'] ?? 0);

                        if ($n->valor < $min || $n->valor > $max) {
                            $errores[] = "Nota fuera de rango ({$n->valor}) en eval '{$ev->nombre}'.";
                        }
                        if ($dec === 0 && floor((float)$n->valor) != (float)$n->valor) {
                            $errores[] = "Decimales no permitidos en eval '{$ev->nombre}'.";
                        }
                    }
                } else { // QUAL
                    if ($n->valor_qual) {
                        $filled++;
                        $valids = $cfg['qual']['niveles'] ?? ['EN_PROCESO','LOGRADO','DESTACADO','OTRO'];
                        if (!in_array($n->valor_qual, $valids, true)) {
                            $errores[] = "Valor cualitativo invÃ¡lido '{$n->valor_qual}' en eval '{$ev->nombre}'.";
                        }
                    }
                }
            }
        }

        $pctNotas = $totalCeldas > 0 ? round(($filled / $totalCeldas) * 100, 2) : 0.0;

        return [
            'pesosOK'   => $pesosOK,
            'sumaPesos' => $sum,
            'pctNotas'  => $pctNotas,
            'errores'   => array_values(array_unique($errores)),
        ];
    }
}
