<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitConversionRequest;
use App\Models\UnitConversion;
use Illuminate\Http\RedirectResponse;

/**
 * Conversions d'unités (cahier §4.3). Configuration : contrairement aux
 * opérations métier critiques, une conversion peut être supprimée.
 */
class UnitConversionController extends Controller
{
    public function store(StoreUnitConversionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        UnitConversion::query()->updateOrCreate(
            [
                'from_unit_id' => $data['from_unit_id'],
                'to_unit_id' => $data['to_unit_id'],
            ],
            ['factor' => $data['factor']]
        );

        return redirect()
            ->route('units.index')
            ->with('status', 'La conversion a été enregistrée.');
    }

    public function destroy(UnitConversion $conversion): RedirectResponse
    {
        // Autorisation vérifiée côté serveur par le middleware
        // `permission:unit.manage` posé sur la route.
        $conversion->delete();

        return redirect()
            ->route('units.index')
            ->with('status', 'La conversion a été supprimée.');
    }
}
