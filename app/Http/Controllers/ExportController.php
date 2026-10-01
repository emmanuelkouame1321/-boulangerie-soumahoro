<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Export\ExportContent;
use App\Domain\Sites\BakeryContext;
use App\Enums\Role;
use App\Jobs\GenerateExport;
use App\Models\Export;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exports en arrière-plan (cahier §48 et §53-13).
 *
 * Créer un export le place en queue (ADR-021) : l'écran « Téléchargements »
 * affiche l'état et sert le fichier une fois prêt. La permission dépend du
 * type : `report.export` pour les rapports, `inventory.view` pour le grand
 * livre, `audit.view` pour le journal d'audit.
 */
class ExportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $exports = Export::query()
            ->with(['bakery', 'user'])
            ->where('user_id', $user->getKey())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('exports.index', [
            'exports' => $exports,
        ]);
    }

    public function store(Request $request, BakeryContext $context, ExportContent $content): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        /** @var array{kind: string, format: string, filters?: array<string, mixed>} $data */
        $data = $request->validate([
            'kind' => ['required', 'string', 'max:32'],
            'format' => ['required', 'in:csv,xls'],
            'filters' => ['array'],
            'filters.*' => ['nullable', 'string', 'max:64'],
        ]);

        $permission = $this->permissionFor($data['kind']);

        if ($permission === null) {
            abort(404);
        }

        abort_unless($user->can($permission), 403);

        $filters = $data['filters'] ?? [];

        // Périmètre : hors Super-Admin, l'export est toujours borné au
        // site courant — jamais la consolidation (fail-closed).
        $current = $context->current();

        if (! $user->hasRole(Role::SuperAdmin->value)) {
            abort_if($current === null, 403);

            $filters['site'] = (string) $current->getKey();
        }

        $export = Export::query()->create([
            'bakery_id' => $current?->getKey(),
            'user_id' => $user->getKey(),
            'kind' => $data['kind'],
            'format' => $data['format'],
            'label' => $content->label($data['kind']),
            'filters' => $filters,
            'status' => Export::STATUS_PENDING,
        ]);

        GenerateExport::dispatch($export);

        return redirect()
            ->route('exports.index')
            ->with('status', "Export « {$export->label} » en préparation — il apparaîtra ici dès qu’il est prêt.");
    }

    public function download(Request $request, Export $export): StreamedResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        // Le fichier n'appartient qu'à son demandeur : le scope par site
        // et ce contrôle ferment l'accès aux autres comptes.
        abort_unless($export->user_id === $user->getKey(), 403);
        abort_unless($export->isReady() && ! $export->isExpired(), 410);

        $disk = Storage::disk('local');
        abort_unless($disk->exists((string) $export->path), 410);

        return $disk->download(
            (string) $export->path,
            $export->label.'.'.$export->format,
        );
    }

    /**
     * Permission exigée par type d'export, ou null si inconnu.
     */
    private function permissionFor(string $kind): ?string
    {
        if (str_starts_with($kind, 'report:')) {
            return 'report.export';
        }

        if ($kind === ExportContent::KIND_MOVEMENTS) {
            return 'inventory.view';
        }

        if ($kind === ExportContent::KIND_AUDIT) {
            return 'audit.view';
        }

        return null;
    }
}
