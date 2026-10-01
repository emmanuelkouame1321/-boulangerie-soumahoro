<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation d'une clôture journalière (cahier §15).
 */
class StoreDayClosingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sales.close-day') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bakery_id' => ['nullable', 'integer', 'exists:bakeries,id'],
            'closing_date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],

            'entries' => ['nullable', 'array'],
            'entries.*.remaining' => ['nullable', 'numeric', 'min:0'],
            'entries.*.lost' => ['nullable', 'numeric', 'min:0'],
            'entries.*.returned' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'closing_date' => 'date de clôture',
            'entries.*.remaining' => 'quantité restante',
            'entries.*.lost' => 'quantité perdue',
            'entries.*.returned' => 'quantité retournée',
        ];
    }
}
