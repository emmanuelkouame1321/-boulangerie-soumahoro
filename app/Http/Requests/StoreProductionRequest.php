<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation de la création d'une production (cahier §7.1).
 */
class StoreProductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('production.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bakery_id' => ['nullable', 'integer', 'exists:bakeries,id'],
            'recipe_id' => ['required', 'integer', 'exists:recipes,id'],
            'batch_count' => ['required', 'numeric', 'gt:0'],
            'actual_quantity' => ['required', 'numeric', 'min:0'],
            'gap_reason' => ['nullable', 'string', Rule::in(array_keys((array) config('boulangerie.production_gap_reasons', [])))],
            'produced_on' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'recipe_id' => 'recette',
            'batch_count' => 'nombre de lots',
            'actual_quantity' => 'quantité réellement produite',
            'gap_reason' => 'motif de l’écart',
            'produced_on' => 'date de production',
        ];
    }
}
