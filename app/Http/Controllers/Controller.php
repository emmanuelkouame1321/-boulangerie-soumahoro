<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Les contrôleurs restent minces : validation + appel au service métier
    // + réponse. Voir docs/ARCHITECTURE.md.
    use AuthorizesRequests;
}
