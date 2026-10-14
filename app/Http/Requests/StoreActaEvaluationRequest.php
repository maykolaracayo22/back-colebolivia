<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActaEvaluationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'acta_id' => ['required','exists:actas,id'],
            'nombre'  => ['required','string','max:120'],
            'peso'    => ['required','numeric','min:0','max:100'],
            'tipo'    => ['nullable','in:NUM,QUAL'], // opcional, por si eval determinada define su tipo
            'max_points'  => ['nullable','numeric','min:0'],
            'min_required'=> ['nullable','numeric','min:0'],
            'locked'      => ['nullable','boolean'],
        ];
    }
}
