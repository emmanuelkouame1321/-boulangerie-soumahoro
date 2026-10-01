<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product && ($this->user()?->can('update', $product) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'sku' => [
                'required',
                'string',
                'max:64',
                Rule::unique('products', 'sku')->ignore($product instanceof Product ? $product->getKey() : null),
            ],
            'name' => ['required', 'string', 'max:255'],
            'product_category_id' => ['nullable', 'integer', Rule::exists('product_categories', 'id')],
            'type' => ['required', Rule::enum(ProductType::class)],
            'primary_unit_id' => ['required', 'integer', Rule::exists('units', 'id')],
            'secondary_unit_id' => ['nullable', 'integer', 'different:primary_unit_id', Rule::exists('units', 'id')],
            'purchase_price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'sale_price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'wholesale_price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'alert_threshold' => ['nullable', 'numeric', 'min:0'],
            'alert_mode' => ['required', 'string', Rule::in(array_keys((array) config('boulangerie.alert_modes', [])))],
            'shelf_life_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
