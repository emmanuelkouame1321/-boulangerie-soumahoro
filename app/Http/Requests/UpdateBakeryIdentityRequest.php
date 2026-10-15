<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Sites\BakeryContext;
use App\Models\Bakery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Modification de l'identité du site courant depuis les paramètres
 * (cahier §19.2, ADR-033).
 *
 * Le site ciblé n'est jamais transmis par le formulaire : il est lu dans le
 * contexte de site courant (source unique de vérité), ce qui interdit de
 * modifier un site que l'utilisateur n'a pas ouvert. L'autorisation reste
 * vérifiée côté serveur par la Policy.
 */
class UpdateBakeryIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $bakery = $this->bakery();

        return $bakery instanceof Bakery
            && ($this->user()?->can('update', $bakery) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('bakeries', 'code')->ignoreModel($this->bakery()),
            ],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'remove_logo' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'Ce code est déjà utilisé par une autre boulangerie.',
            'logo.image' => 'Le logo doit être une image (JPEG, PNG, WebP ou GIF).',
            'logo.max' => 'Le logo ne doit pas dépasser 4 Mo.',
        ];
    }

    public function bakery(): ?Bakery
    {
        return app(BakeryContext::class)->current();
    }
}
