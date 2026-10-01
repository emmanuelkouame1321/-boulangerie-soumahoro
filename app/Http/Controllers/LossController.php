<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Inventory\ExpiryService;
use App\Domain\Inventory\LossService;
use App\Domain\Sites\BakeryContext;
use App\Enums\LossReason;
use App\Enums\ProductType;
use App\Exceptions\EmptyLossException;
use App\Exceptions\InsufficientLotException;
use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreLossRequest;
use App\Models\Bakery;
use App\Models\LossRecord;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Pertes et invendus (cahier §8.2, §15 et §50).
 *
 * Contrôleur mince : autorisation par LossRecordPolicy, validation par
 * Form Request, enregistrement par `LossService`. Les statistiques de
 * l'écran sont calculées côté serveur (pertes du jour, lots expirés).
 */
class LossController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly LossService $losses,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', LossRecord::class);

        $search = trim((string) $request->query('search', ''));
        $reason = (string) $request->query('reason', '');
        $date = (string) $request->query('date', '');

        $losses = LossRecord::query()
            ->with(['createdBy'])
            ->withCount('items')
            ->search($search)
            ->when($reason !== '', fn ($query) => $query->where('reason', $reason))
            ->when($date !== '', fn ($query) => $query->whereDate('occurred_on', $date))
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $today = Carbon::today();

        $todayRecords = LossRecord::query()
            ->whereDate('occurred_on', $today)
            ->get(['total_value']);

        $monthStart = $today->copy()->startOfMonth();

        $monthValue = (int) LossRecord::query()
            ->whereDate('occurred_on', '>=', $monthStart)
            ->sum('total_value');

        return view('losses.index', [
            'losses' => $losses,
            'search' => $search,
            'reason' => $reason,
            'date' => $date,
            'reasonOptions' => config('boulangerie.loss_reasons', []),
            'todayValue' => (int) $todayRecords->sum('total_value'),
            'todayCount' => $todayRecords->count(),
            'monthValue' => $monthValue,
            'expiredLots' => ExpiryService::expiredLotsQuery()->count(),
            'canCreate' => $request->user()?->can('create', LossRecord::class) ?? false,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', LossRecord::class);

        $products = Product::query()
            ->active()
            ->where('type', ProductType::FinishedGood->value)
            ->orderBy('name')
            ->get();

        /** @var array<int, string> $quantities */
        $quantities = Stock::query()
            ->whereIn('product_id', $products->pluck('id'))
            ->get()
            ->mapWithKeys(static fn (Stock $stock): array => [$stock->product_id => (string) $stock->quantity])
            ->all();

        $productOptions = $products->mapWithKeys(static function (Product $product) use ($quantities): array {
            $quantity = Format::stock($quantities[$product->getKey()] ?? '0');

            return [
                $product->getKey() => sprintf(
                    '%s — stock %s',
                    $product->name,
                    $quantity
                ),
            ];
        })->all();

        return view('losses.create', [
            'productOptions' => $productOptions,
            'reasonOptions' => config('boulangerie.loss_reasons', []),
        ]);
    }

    public function store(StoreLossRequest $request, BakeryContext $context): RedirectResponse
    {
        $this->authorize('create', LossRecord::class);

        $data = $request->validated();

        $bakery = $this->resolveBakery($context, $data['bakery_id'] ?? null);

        if ($bakery === null) {
            return back()->withInput()->with('error', 'Sélectionnez une boulangerie avant de déclarer la perte.');
        }

        $user = $request->user();

        try {
            $record = $this->losses->record(
                bakery: $bakery,
                reason: LossReason::from((string) $data['reason']),
                lines: [[
                    'product_id' => (int) $data['product_id'],
                    'quantity' => $data['quantity'],
                    'comment' => $data['comment'] ?? null,
                ]],
                user: $user instanceof User ? $user : null,
                note: $data['note'] ?? null,
            );
        } catch (EmptyLossException|InsufficientLotException|InsufficientStockException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('losses.show', $record)
            ->with('status', "La perte {$record->reference} a été enregistrée.");
    }

    public function show(LossRecord $loss): View
    {
        $this->authorize('view', LossRecord::class);

        $loss->load(['bakery', 'createdBy', 'items.product.primaryUnit', 'items.batch']);

        return view('losses.show', [
            'loss' => $loss,
        ]);
    }

    private function resolveBakery(BakeryContext $context, mixed $bakeryId): ?Bakery
    {
        $available = $context->availableBakeries();

        $targetId = $bakeryId !== null
            ? (int) $bakeryId
            : $context->current()?->getKey();

        if ($targetId === null) {
            return null;
        }

        return $available->firstWhere('id', $targetId);
    }
}
