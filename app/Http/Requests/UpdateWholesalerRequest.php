<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Mise à jour d'une fiche grossiste (cahier §12.1).
 */
class UpdateWholesalerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('wholesaler.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:255'],
            'zone' => ['nullable', 'string', 'max:120'],
            'client_type' => ['nullable', 'string', 'max:120'],
            'price_mode' => ['required', Rule::in(array_keys((array) config('boulangerie.wholesaler.price_modes')))],
            'usual_quantity' => ['required', 'numeric', 'min:0'],
            'payment_condition' => ['nullable', Rule::in(array_keys((array) config('boulangerie.wholesaler_conditions')))],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'joined_on' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom ou raison sociale',
            'contact_name' => 'personne responsable',
            'price_mode' => 'grille de prix',
            'usual_quantity' => 'quantité habituelle',
            'payment_condition' => 'conditions de paiement',
        ];
    }
}
