<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitConversionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('unit.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from_unit_id' => ['required', 'integer', Rule::exists('units', 'id')],
            'to_unit_id' => ['required', 'integer', 'different:from_unit_id', Rule::exists('units', 'id')],
            'factor' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to_unit_id.different' => 'Les deux unités doivent être différentes.',
            'factor.gt' => 'Le facteur doit être strictement positif.',
        ];
    }
}
