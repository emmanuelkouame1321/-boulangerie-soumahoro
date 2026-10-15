<?php

declare(strict_types=1);

namespace App\Http\Requests\Security;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Mot de passe exigé pour modifier la vérification en deux étapes
 * (cahier §31).
 *
 * Activer, désactiver ou régénérer les codes de secours change la
 * protection du compte : une session laissée ouverte ne suffit pas, le
 * mot de passe est redemandé à chaque fois.
 */
class TwoFactorPasswordRequest extends FormRequest
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
            'password' => ['required', 'string', 'current_password'],
        ];
    }
}
