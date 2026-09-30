<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EvaluateSmartAbstractExerciseRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'document_text' => ['required', 'string', 'min:30', 'max:100000'],
        ];
    }
}
