<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PurchaseOrder::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bakery_id' => ['nullable', 'integer', Rule::exists('bakeries', 'id')],
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
            'expected_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'integer', 'min:0', 'max:999999999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Ajoutez au moins une ligne à la commande.',
            'items.min' => 'Ajoutez au moins une ligne à la commande.',
            'items.*.product_id.required' => 'Chaque ligne doit désigner un produit.',
            'items.*.quantity.gt' => 'Les quantités commandées doivent être strictement positives.',
        ];
    }
}
