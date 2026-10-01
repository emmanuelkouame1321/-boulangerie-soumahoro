<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Ajustement d'une ligne de paie (primes, avances, retenues) — cahier §17.
 */
class UpdatePayrollItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payroll.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'base_salary' => ['nullable', 'integer', 'min:0'],
            'bonuses' => ['nullable', 'integer', 'min:0'],
            'advances' => ['nullable', 'integer', 'min:0'],
            'deductions' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
