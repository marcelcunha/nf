<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateImportScheduleRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'times' => ['required', 'array', 'min:1', 'max:3'],
            'times.*' => ['required', 'date_format:H:i', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'times.required' => 'Informe ao menos um horário.',
            'times.min' => 'Informe ao menos um horário.',
            'times.max' => 'Informe no máximo 3 horários.',
            'times.*.required' => 'Informe o horário.',
            'times.*.date_format' => 'Horário inválido.',
            'times.*.distinct' => 'Horário repetido.',
        ];
    }
}
