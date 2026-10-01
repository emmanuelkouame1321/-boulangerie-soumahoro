<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ContractType;
use App\Enums\EmployeeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation d'une fiche employé (cahier §16.1).
 */
class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employee.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:255'],
            'position' => ['required', Rule::in(array_keys((array) config('boulangerie.positions')))],
            'contract_type' => ['required', Rule::in(ContractType::values())],
            'base_salary' => ['required', 'integer', 'min:0'],
            'hired_on' => ['nullable', 'date'],
            'status' => ['required', Rule::in(EmployeeStatus::values())],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'position' => 'poste',
            'contract_type' => 'type de contrat',
            'base_salary' => 'salaire de base',
            'hired_on' => "date d'embauche",
            'admin_note' => 'informations administratives',
        ];
    }
}
