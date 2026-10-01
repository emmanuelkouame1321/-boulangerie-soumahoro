<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use App\Models\UnitConversion;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Administration des unités de mesure et de leurs conversions (cahier §4.3).
 *
 * Contrôleur mince : autorisation par UnitPolicy, validation par Form
 * Requests, persistance par Eloquent. Les calculs de conversion vivent
 * dans App\Domain\Measurement\UnitConversionService.
 */
class UnitController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Unit::class);

        $units = Unit::query()->ordered()->get();

        $conversions = UnitConversion::query()
            ->with(['fromUnit', 'toUnit'])
            ->join('units as from_units', 'from_units.id', '=', 'unit_conversions.from_unit_id')
            ->orderBy('from_units.kind')
            ->orderBy('from_units.position')
            ->select('unit_conversions.*')
            ->get();

        return view('settings.units.index', [
            'units' => $units,
            'conversions' => $conversions,
            'unitOptions' => $units->pluck('label', 'id')->all(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Unit::class);

        return view('settings.units.create', [
            'unit' => new Unit(['is_active' => true, 'position' => 0, 'kind' => 'count']),
        ]);
    }

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        $unit = Unit::create($request->validated());

        return redirect()
            ->route('units.index')
            ->with('status', "L’unité « {$unit->label} » a été créée.");
    }

    public function edit(Unit $unit): View
    {
        $this->authorize('update', $unit);

        return view('settings.units.edit', ['unit' => $unit]);
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return redirect()
            ->route('units.index')
            ->with('status', "L’unité « {$unit->label} » a été mise à jour.");
    }
}
