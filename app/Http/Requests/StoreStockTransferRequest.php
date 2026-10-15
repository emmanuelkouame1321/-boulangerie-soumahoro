<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation d'un transfert inter-sites (cahier §19.3).
 *
 * `bakery_id` (site expéditeur) est facultatif : à défaut, le site courant
 * du contexte est utilisé. Le lot (`production_batch_id`) est facultatif —
 * sans lui, l'imputation FEFO s'applique côté service. Les règles
 * métier (deux sites distincts, lot appartenant à l'expéditeur, quantité
 * disponible) sont vérifiées par `StockTransferService`, qui renvoie des
 * messages clairs.
 */
class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('transfer.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bakery_id' => ['nullable', 'integer', 'exists:bakeries,id'],
            'destination_bakery_id' => ['required', 'integer', 'exists:bakeries,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'production_batch_id' => ['nullable', 'integer', 'exists:production_batches,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'transferred_on' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'bakery_id' => 'site expéditeur',
            'destination_bakery_id' => 'site destinataire',
            'product_id' => 'produit',
            'production_batch_id' => 'lot',
            'quantity' => 'quantité',
            'transferred_on' => 'date du transfert',
        ];
    }
}
