<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertActaNoteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'acta_id'       => ['required','exists:actas,id'],
            'evaluation_id' => ['required','exists:acta_evaluations,id'],
            'student_id'    => ['required','exists:students,id'],
            'tipo'          => ['required', Rule::in(['NUM','QUAL'])],
            'valor'         => ['nullable','numeric'], // validacion fina se hace en el servicio
            'valor_qual'    => ['nullable','string','max:50'],
        ];
    }
}
