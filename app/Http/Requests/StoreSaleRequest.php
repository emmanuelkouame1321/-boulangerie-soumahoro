<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation d'une vente au détail (cahier §14).
 */
class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sales.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bakery_id' => ['nullable', 'integer', 'exists:bakeries,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'payment_method' => ['nullable', 'string', Rule::in(array_keys((array) config('boulangerie.payment_methods', [])))],
            'paid_amount' => ['nullable', 'integer', 'min:0'],
            'sold_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'integer', 'min:0'],
            'items.*.discount' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'client',
            'payment_method' => 'mode de paiement',
            'paid_amount' => 'montant payé',
            'items' => 'articles',
            'items.*.product_id' => 'produit',
            'items.*.quantity' => 'quantité',
            'items.*.unit_price' => 'prix unitaire',
            'items.*.discount' => 'remise',
        ];
    }
}
