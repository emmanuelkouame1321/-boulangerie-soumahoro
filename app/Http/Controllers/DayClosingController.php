<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Sales\DayClosingService;
use App\Domain\Sites\BakeryContext;
use App\Exceptions\DayClosingAlreadyExistsException;
use App\Exceptions\DayClosingGapJustificationRequiredException;
use App\Http\Requests\StoreDayClosingRequest;
use App\Models\Bakery;
use App\Models\DayClosing;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Clôtures journalières (cahier §15).
 *
 * Contrôleur mince : autorisation par DayClosingPolicy, validation par Form
 * Request, calcul par DayClosingService. Une clôture déjà existante pour
 * la date est refusée clairement.
 */
class DayClosingController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly DayClosingService $closings,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', DayClosing::class);

        $closings = DayClosing::query()
            ->with('bakery')
            ->withCount('items')
            ->orderByDesc('closing_date')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('sales.closings.index', [
            'closings' => $closings,
        ]);
    }

    public function create(Request $request, BakeryContext $context): View
    {
        $this->authorize('create', DayClosing::class);

        $date = Carbon::parse((string) $request->query('closing_date', now()->toDateString()));
        $bakery = $context->current() ?? $context->availableBakeries()->first();

        return view('sales.closings.create', [
            'date' => $date,
            'snapshot' => $bakery !== null ? $this->closings->snapshot($bakery, $date) : [],
            'bakeryOptions' => $context->availableBakeries()->pluck('name', 'id')->all(),
            'currentBakeryId' => $bakery?->getKey(),
        ]);
    }

    public function store(StoreDayClosingRequest $request, BakeryContext $context): RedirectResponse
    {
        $this->authorize('create', DayClosing::class);

        $data = $request->validated();

        $bakery = $this->resolveBakery($context, $data['bakery_id'] ?? null);

        if ($bakery === null) {
            return back()->withInput()->with('error', 'Sélectionnez une boulangerie avant de clôturer.');
        }

        /** @var array<int, array{remaining?: mixed, lost?: mixed, returned?: mixed}> $normalized */
        $normalized = [];

        if (is_array($data['entries'] ?? null)) {
            foreach ($data['entries'] as $productId => $entry) {
                $normalized[(int) $productId] = array_filter([
                    'remaining' => $entry['remaining'] ?? null,
                    'lost' => $entry['lost'] ?? null,
                    'returned' => $entry['returned'] ?? null,
                ], static fn ($value): bool => $value !== null && $value !== '');
            }
        }

        $user = $request->user();
        $date = Carbon::parse($data['closing_date']);

        try {
            $closing = $this->closings->close(
                bakery: $bakery,
                date: $date,
                entries: $normalized,
                user: $user instanceof User ? $user : null,
                note: $data['note'] ?? null,
            );
        } catch (DayClosingAlreadyExistsException|DayClosingGapJustificationRequiredException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('sales.closings.show', $closing)
            ->with('status', "La clôture {$closing->reference} a été enregistrée.");
    }

    public function show(DayClosing $closing): View
    {
        $this->authorize('view', DayClosing::class);

        $closing->load(['bakery', 'createdBy', 'items.product.primaryUnit']);

        return view('sales.closings.show', [
            'closing' => $closing,
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
