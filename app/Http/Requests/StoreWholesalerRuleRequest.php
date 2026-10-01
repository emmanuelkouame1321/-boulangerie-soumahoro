<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\RebateBasis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Création d'un palier de ristourne (cahier §13).
 *
 * Les seuils et taux sont administrables : aucune valeur n'est codée en
 * dur. Le taux est exprimé en pourcentage (0 à 100).
 */
class StoreWholesalerRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rebate.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'basis' => ['required', Rule::in(RebateBasis::values())],
            'product_category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'min_threshold' => ['required', 'integer', 'min:0'],
            'max_threshold' => ['nullable', 'integer', 'min:0', 'gte:min_threshold'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'basis' => 'base de calcul',
            'product_category_id' => 'famille de produits',
            'min_threshold' => 'seuil bas',
            'max_threshold' => 'seuil haut',
            'rate' => 'taux',
        ];
    }
}
