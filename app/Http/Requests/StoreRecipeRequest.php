<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ProductType;
use App\Enums\RecipeDeductionMode;
use App\Models\Recipe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation d'une recette et de ses ressources (cahier §5 et §6).
 *
 * Le produit fini et chaque ressource sont vérifiés vis-à-vis du type :
 * une recette cible un produit fini et ne consomme que des ressources
 * non finies. Le mode de déduction détermine la nature de la ressource.
 */
class StoreRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Recipe::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $resourceTypes = [
            ProductType::RawMaterial->value,
            ProductType::SemiFinished->value,
            ProductType::Consumable->value,
            ProductType::Packaging->value,
            ProductType::Resource->value,
        ];

        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('type', ProductType::FinishedGood->value),
            ],
            'name' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:64', Rule::unique('recipes', 'reference')],
            'yield_quantity' => ['required', 'numeric', 'gt:0'],
            'production_time_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'shelf_life_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'allowed_loss_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
            'note' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->whereIn('type', $resourceTypes),
            ],
            'items.*.unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.deduction_mode' => ['required', Rule::enum(RecipeDeductionMode::class)],
            'items.*.unit_cost' => [
                'nullable',
                'integer',
                'min:0',
                'max:999999999999',
                'required_if:items.*.deduction_mode,monetary,cumulated',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.exists' => 'La recette doit cibler un produit fini existant.',
            'reference.unique' => 'Cette référence de recette est déjà utilisée.',
            'items.required' => 'Ajoutez au moins une ressource à la recette.',
            'items.min' => 'Ajoutez au moins une ressource à la recette.',
            'items.*.product_id.required' => 'Chaque ressource doit désigner un produit.',
            'items.*.product_id.exists' => 'Chaque ressource doit désigner une matière ou une ressource non finie.',
            'items.*.quantity.gt' => 'Les quantités par lot doivent être strictement positives.',
            'items.*.unit_cost.required' => 'Renseignez le coût unitaire des ressources monétaires ou cumulées.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'produit fini',
            'yield_quantity' => 'rendement par lot',
            'production_time_minutes' => 'durée de production',
            'shelf_life_days' => 'durée limite de vente',
            'allowed_loss_percent' => 'pertes autorisées',
            'items' => 'ressources',
            'items.*.product_id' => 'ressource',
            'items.*.quantity' => 'quantité',
            'items.*.deduction_mode' => 'mode de déduction',
            'items.*.unit_cost' => 'coût unitaire',
        ];
    }
}
