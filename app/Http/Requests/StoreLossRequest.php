<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation d'une déclaration de perte (cahier §8.2).
 *
 * Aucun lot n'est saisi : la perte est imputée aux lots du produit
 * dans l'ordre FEFO par `LotService` (les lots expirés compris). La
 * valorisation est calculée côté serveur à l'enregistrement.
 */
class StoreLossRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('loss.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bakery_id' => ['nullable', 'integer', 'exists:bakeries,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', Rule::in(array_keys((array) config('boulangerie.loss_reasons', [])))],
            'comment' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'produit',
            'quantity' => 'quantité',
            'reason' => 'motif',
        ];
    }
}
