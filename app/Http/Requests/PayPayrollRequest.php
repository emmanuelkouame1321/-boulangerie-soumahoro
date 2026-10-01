<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Versement des salaires d'une période (cahier §17).
 */
class PayPayrollRequest extends FormRequest
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
            'method' => ['required', Rule::in(array_keys((array) config('boulangerie.payment_methods')))],
            'paid_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'method' => 'mode de paiement',
            'paid_at' => 'date de paiement',
        ];
    }
}
