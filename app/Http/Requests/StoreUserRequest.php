<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
            'bakeries' => ['array'],
            'bakeries.*' => ['integer', Rule::exists('bakeries', 'id')],
            'default_bakery_id' => ['nullable', 'integer', Rule::in($this->input('bakeries', []))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'default_bakery_id.in' => 'Le site par défaut doit faire partie des sites affectés.',
        ];
    }
}
