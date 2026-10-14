<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Acta;

class SaveItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actaId = $this->route('id');
        $acta = Acta::find($actaId);
        if (!$acta) return false;

        // Use policy
        return $this->user() && $this->user()->can('update', $acta);
    }

    public function rules(): array
    {
        $actaId = $this->route('id');
        $acta = Acta::find($actaId);
        $peMax = 100;
        if ($acta && is_array($acta->metrics ?? null) && isset($acta->metrics['pe_max'])) {
            $peMax = (int)$acta->metrics['pe_max'];
        }

        return [
            'notes' => ['sometimes','array'],
            'notes.*.student_id' => ['required','exists:students,id'],
            'notes.*.evaluation_id' => ['required','exists:acta_evaluations,id'],
            'notes.*.valor' => ['nullable','numeric','min:0','max:100'],

            'auto' => ['sometimes','array'],
            'auto.*.student_id' => ['required','exists:students,id'],
            'auto.*.valor' => ['required','integer','min:0','max:5'],

            'pe' => ['sometimes','array'],
            'pe.*.student_id' => ['required','exists:students,id'],
            'pe.*.valor' => ['required','integer','min:0','max:'.$peMax],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function($v) {
            $data = $this->validated();
            $actaId = $this->route('id');

            // Ensure every evaluation_id belongs to this acta
            if (!empty($data['notes'])) {
                foreach ($data['notes'] as $n) {
                    if (!isset($n['evaluation_id'])) continue;
                    $exists = \DB::table('acta_evaluations')
                        ->where('id', $n['evaluation_id'])
                        ->where('acta_id', $actaId)
                        ->exists();
                    if (!$exists) {
                        $v->errors()->add('notes', 'Una nota referencia una evaluaciÃ³n que no pertenece a esta acta.');
                    }
                }
            }
        });
    }
}
