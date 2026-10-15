<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Recipe;
use Illuminate\Validation\Rule;

/**
 * Validation de la modification d'une recette (cahier §5 et §6).
 *
 * Mêmes règles que la création, à ceci près que la référence peut rester
 * celle de la recette courante.
 */
class UpdateRecipeRequest extends StoreRecipeRequest
{
    public function authorize(): bool
    {
        $recipe = $this->route('recipe');

        return $recipe instanceof Recipe
            && ($this->user()?->can('update', $recipe) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $recipe = $this->route('recipe');

        $rules['reference'] = [
            'nullable',
            'string',
            'max:64',
            Rule::unique('recipes', 'reference')->ignore(
                $recipe instanceof Recipe ? $recipe->getKey() : null
            ),
        ];

        return $rules;
    }
}
