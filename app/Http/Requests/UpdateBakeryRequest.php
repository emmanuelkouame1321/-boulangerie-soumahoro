<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BakeryStatus;
use App\Models\Bakery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBakeryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $bakery = $this->route('bakery');

        return $bakery instanceof Bakery
            && ($this->user()?->can('update', $bakery) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $bakery = $this->route('bakery');

        return [
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('bakeries', 'code')->ignoreModel($bakery),
            ],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'status' => ['required', Rule::enum(BakeryStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'Ce code est déjà utilisé par une autre boulangerie.',
        ];
    }
}
