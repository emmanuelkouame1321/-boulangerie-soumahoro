<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $unit = $this->route('unit');

        return $unit instanceof Unit && ($this->user()?->can('update', $unit) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $unit = $this->route('unit');

        return [
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('units', 'code')->ignore($unit instanceof Unit ? $unit->getKey() : null),
            ],
            'label' => ['required', 'string', 'max:255'],
            'symbol' => ['required', 'string', 'max:16'],
            'kind' => ['required', 'string', 'max:16'],
            'position' => ['required', 'integer', 'min:0', 'max:65000'],
            'is_active' => ['boolean'],
        ];
    }
}
