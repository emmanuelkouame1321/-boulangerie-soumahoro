<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Code du second facteur, à la connexion (cahier §31).
 *
 * Le champ accepte aussi bien un code TOTP à 6 chiffres qu'un code de
 * secours au format `ABCDE-FGHIJ`.
 */
class TwoFactorChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:16'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Saisissez le code affiché par votre application d’authentification.',
        ];
    }
}
