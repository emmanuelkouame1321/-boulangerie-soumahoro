<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && ($this->user()?->can('update', $user) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($target instanceof User ? $target->getKey() : null),
            ],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
            'bakeries' => ['array'],
            'bakeries.*' => ['integer', Rule::exists('bakeries', 'id')],
            'default_bakery_id' => ['nullable', 'integer', Rule::in($this->input('bakeries', []))],
        ];
    }
}
