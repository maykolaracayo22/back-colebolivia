<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActaRequest extends FormRequest
{
    public function authorize(): bool { return true; } // usa Policies en el Controller

    public function rules(): array
    {
        return [
            'ref'         => ['required','string','max:190','unique:actas,ref'],
            'year'        => ['required','digits:4'],
            'nivel'       => ['nullable', Rule::in(['inicial','primaria','secundaria'])],
            'grado'       => ['nullable','string','max:50'],
            'seccion'     => ['nullable','string','max:10'],
            'materia_id'  => ['nullable','exists:subjects,id'],
            'materia'     => ['nullable','string','max:120'],
            'teacher_id'  => ['nullable','exists:teachers,id'],
            'status'      => ['required', Rule::in(['DRAFT','LOCKED','PUBLISHED'])],
        ];
    }
}
