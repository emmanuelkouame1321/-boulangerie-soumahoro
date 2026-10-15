<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Inventory\StockTransferService;
use App\Domain\Sites\BakeryContext;
use App\Enums\StockTransferStatus;
use App\Exceptions\InsufficientLotException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStockTransferException;
use App\Http\Requests\StoreStockTransferRequest;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\StockTransfer;
use App\Models\User;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Transferts inter-sites (cahier §19.3).
 *
 * Contrôleur mince : autorisation par StockTransferPolicy, validation par
 * Form Request, orchestration par `StockTransferService`. Aucun stock
 * n'est écrit ici : l'expédition et la réception passent par le service,
 * seul responsable des mouvements et des lots.
 *
 * La liste ne montre que les transferts touchant un site accessible à
 * l'utilisateur, et la résolution de route ne charge un transfert que
 * dans cette même portée (404 sinon) — le modèle n'ayant pas de scope
 * global par site.
 */
class StockTransferController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly StockTransferService $transfers,
    ) {}

    public function index(Request $request, BakeryContext $context): View
    {
        $this->authorize('viewAny', StockTransfer::class);

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $siteIds = $context->availableBakeries()->pluck('id')->all();

        $transfers = StockTransfer::query()
            ->involvingAny($siteIds)
            ->with(['sourceBakery', 'destinationBakery', 'product.primaryUnit', 'unit'])
            ->search($search)
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('transferred_on')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('inventory.transfers.index', [
            'transfers' => $transfers,
            'search' => $search,
            'status' => $status,
            'statusOptions' => $this->statusOptions(),
            'canCreate' => $request->user()?->can('create', StockTransfer::class) ?? false,
        ]);
    }

    public function create(BakeryContext $context): View
    {
        $this->authorize('create', StockTransfer::class);

        $sites = $context->availableBakeries();
        $sourceId = $context->current()?->getKey() ?? $sites->first()?->getKey();

        $products = Product::query()->active()->orderBy('name')->get();

        // Le destinataire n'est pas forcément un site accessible à
        // l'utilisateur : un transfert relie deux sites, et c'est le
        // réceptionnaire, côté destinataire, qui confirme l'arrivée.
        $destinationOptions = Bakery::selectable()
            ->mapWithKeys(static fn (Bakery $bakery): array => [$bakery->getKey() => $bakery->name])
            ->all();

        // Lots réellement disponibles au site courant : la sélection d'un
        // lot reste facultative (imputation FEFO par défaut).
        $batchOptions = ProductionBatch::query()
            ->with('product')
            ->where('available_quantity', '>', 0)
            ->orderBy('expires_on')
            ->get()
            ->mapWithKeys(static function (ProductionBatch $batch): array {
                return [
                    $batch->getKey() => sprintf(
                        '%s — %s (%s)',
                        (string) data_get($batch, 'product.name', 'Produit'),
                        $batch->reference,
                        Format::stock($batch->available_quantity),
                    ),
                ];
            })
            ->all();

        return view('inventory.transfers.create', [
            'sites' => $sites,
            'sourceId' => $sourceId,
            'productOptions' => $products->mapWithKeys(
                static fn (Product $product): array => [$product->getKey() => $product->name]
            )->all(),
            'destinationOptions' => $destinationOptions,
            'batchOptions' => $batchOptions,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(StoreStockTransferRequest $request, BakeryContext $context): RedirectResponse
    {
        $this->authorize('create', StockTransfer::class);

        $data = $request->validated();

        $source = $this->resolveSite($context, $data['bakery_id'] ?? null);
        $destination = Bakery::query()->find((int) $data['destination_bakery_id']);

        if ($source === null || $destination === null) {
            return back()->withInput()->with('error', 'Sélectionnez un site expéditeur autorisé et un site destinataire valide.');
        }

        $product = Product::query()->find((int) $data['product_id']);

        if ($product === null) {
            return back()->withInput()->with('error', 'Produit introuvable.');
        }

        $batch = null;

        if (! empty($data['production_batch_id'])) {
            $batch = ProductionBatch::withoutBakeryScope()
                ->whereKey((int) $data['production_batch_id'])
                ->first();
        }

        $user = $request->user();

        try {
            $transfer = $this->transfers->create(
                source: $source,
                destination: $destination,
                product: $product,
                quantity: $data['quantity'],
                batch: $batch,
                transferredOn: isset($data['transferred_on'])
                    ? Carbon::parse((string) $data['transferred_on'])
                    : null,
                user: $user instanceof User ? $user : null,
                reason: $data['reason'] ?? null,
                note: $data['note'] ?? null,
            );
        } catch (InvalidStockTransferException|InsufficientLotException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('inventory.transfers.show', $transfer)
            ->with('status', "Le transfert {$transfer->reference} a été préparé. Expédiez-le pour sortir le stock.");
    }

    public function show(StockTransfer $transfer): View
    {
        $this->authorize('view', $transfer);

        $transfer->load([
            'sourceBakery',
            'destinationBakery',
            'product.primaryUnit',
            'unit',
            'batch',
            'createdBy',
            'sentBy',
            'receivedBy',
        ]);

        $user = request()->user();

        return view('inventory.transfers.show', [
            'transfer' => $transfer,
            'canSend' => $user?->can('send', $transfer) ?? false,
            'canReceive' => $user?->can('receive', $transfer) ?? false,
            'canCancel' => $user?->can('cancel', $transfer) ?? false,
        ]);
    }

    public function send(StockTransfer $transfer): RedirectResponse
    {
        $this->authorize('send', $transfer);

        $user = request()->user();

        try {
            $transfer = $this->transfers->send($transfer, $user instanceof User ? $user : null);
        } catch (InvalidStockTransferException|InsufficientLotException|InsufficientStockException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('inventory.transfers.show', $transfer)
            ->with('status', "Le transfert {$transfer->reference} a été expédié.");
    }

    public function receive(StockTransfer $transfer): RedirectResponse
    {
        $this->authorize('receive', $transfer);

        $user = request()->user();

        try {
            $transfer = $this->transfers->receive($transfer, $user instanceof User ? $user : null);
        } catch (InvalidStockTransferException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('inventory.transfers.show', $transfer)
            ->with('status', "Le transfert {$transfer->reference} a été réceptionné.");
    }

    public function cancel(StockTransfer $transfer): RedirectResponse
    {
        $this->authorize('cancel', $transfer);

        try {
            $transfer = $this->transfers->cancel($transfer);
        } catch (InvalidStockTransferException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('inventory.transfers.show', $transfer)
            ->with('status', "Le transfert {$transfer->reference} a été annulé.");
    }

    /**
     * @return array<string, string>
     */
    private function statusOptions(): array
    {
        $options = [];

        foreach (StockTransferStatus::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }

    private function resolveSite(BakeryContext $context, mixed $bakeryId): ?Bakery
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
