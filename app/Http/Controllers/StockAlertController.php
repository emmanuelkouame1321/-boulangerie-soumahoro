<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StockAlertStatus;
use App\Enums\StockAlertType;
use App\Models\StockAlert;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Centre d'alertes (cahier §22).
 *
 * Liste les alertes (stock, péremption, pertes, rendement) dans l'ordre
 * de déclenchement et permet leur acquittement (`alert.acknowledge`).
 * Une alerte à condition se résout d'elle-même quand la condition
 * disparaît ; une alerte événementielle reste ouverte jusqu'à être
 * acquittée puis refermée par le cycle de vie de son module.
 */
class StockAlertController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', StockAlert::class);

        $status = (string) $request->query('status', StockAlertStatus::Open->value);
        $type = (string) $request->query('type', '');

        $alerts = StockAlert::query()
            ->with(['product.primaryUnit'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->orderByDesc('triggered_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('notifications.index', [
            'alerts' => $alerts,
            'status' => $status,
            'type' => $type,
            'typeOptions' => array_map(
                static fn (StockAlertType $alertType): string => $alertType->label(),
                StockAlertType::cases()
            ),
            'statusOptions' => array_map(
                static fn (StockAlertStatus $alertStatus): string => $alertStatus->label(),
                StockAlertStatus::cases()
            ),
            'openCount' => StockAlert::query()->where('status', StockAlertStatus::Open->value)->count(),
            'canAcknowledge' => $request->user()?->can('acknowledge', StockAlert::class) ?? false,
        ]);
    }

    public function acknowledge(Request $request, StockAlert $alert): RedirectResponse
    {
        $this->authorize('acknowledge', StockAlert::class);

        if ($alert->status === StockAlertStatus::Open) {
            $user = $request->user();

            $alert->update([
                'status' => StockAlertStatus::Acknowledged,
                'acknowledged_by' => $user instanceof User ? $user->getKey() : null,
                'acknowledged_at' => now(),
            ]);
        }

        return back()->with('status', "L’alerte « {$alert->type->label()} » a été acquittée.");
    }
}
