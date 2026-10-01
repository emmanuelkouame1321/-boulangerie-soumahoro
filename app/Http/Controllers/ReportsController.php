<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Export\ExportContent;
use App\Domain\Reporting\ReportingService;
use App\Domain\Sites\BakeryContext;
use App\Enums\Role;
use App\Models\Bakery;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Rapports et pilotage (cahier §33, §55 et Design System §83, §84).
 *
 * Le Super-Admin peut choisir la période et le site (ou tous les sites) ;
 * un utilisateur rattaché reste limité à son site courant. Les exports
 * CSV / Excel passent par la file d'attente (écran Téléchargements,
 * ADR-021) ; seule l'impression reste directe (vue imprimable).
 */
class ReportsController extends Controller
{
    public function __construct(
        private readonly ReportingService $reporting,
        private readonly ExportContent $content,
    ) {}

    public function index(Request $request, BakeryContext $context): View
    {
        [$bakeryId, $from, $to] = $this->period($request, $context);

        $year = (int) date('Y', (int) strtotime($from));

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'site' => (string) $request->query('site', ''),
            'bakeryOptions' => $this->bakeryOptions($context),
            'reports' => $this->content->catalogue(),
            'summary' => [
                'revenue' => $this->reporting->sales($bakeryId, $from, $to)['revenue'],
                'sales' => $this->reporting->sales($bakeryId, $from, $to)['sales'],
                'production' => $this->reporting->production($bakeryId, $from, $to)['produced'],
                'losses' => $this->reporting->losses($bakeryId, $from, $to)['value'],
                'expenses' => $this->reporting->expenses($bakeryId, $from, $to)['total'],
                'payroll' => $this->reporting->payroll($bakeryId, $year)['total_net'],
            ],
            'questions' => $this->reporting->questions($bakeryId),
            'trend' => $this->reporting->revenueTrend($bakeryId, 14),
        ]);
    }

    public function show(Request $request, BakeryContext $context, string $kind): View
    {
        [$bakeryId, $from, $to] = $this->period($request, $context);

        return view('reports.show', [
            'kind' => $kind,
            'label' => $this->content->label('report:'.$kind),
            'description' => $this->content->catalogue()[$kind]['description'],
            'from' => $from,
            'to' => $to,
            'site' => (string) $request->query('site', ''),
            'bakeryOptions' => $this->bakeryOptions($context),
            'data' => $this->content->reportData($kind, $bakeryId, $from, $to),
            'canExport' => $request->user()?->can('report.export') ?? false,
        ]);
    }

    /**
     * Vue imprimable : le navigateur imprime en PDF. Les exports CSV et
     * Excel partent en queue (écran Téléchargements).
     */
    public function print(Request $request, BakeryContext $context, string $kind): View
    {
        [$bakeryId, $from, $to] = $this->period($request, $context);

        $data = $this->content->reportData($kind, $bakeryId, $from, $to);

        return view('reports.print', [
            'label' => $this->content->label('report:'.$kind),
            'from' => $from,
            'to' => $to,
            'tables' => $this->content->tables($kind, $data),
        ]);
    }

    /**
     * @return array{0: int|null, 1: string, 2: string}
     */
    private function period(Request $request, BakeryContext $context): array
    {
        $from = (string) $request->query('from', now()->startOfMonth()->toDateString());
        $to = (string) $request->query('to', now()->toDateString());

        $user = $context->user();
        $isSuperAdmin = $user?->hasRole(Role::SuperAdmin->value) ?? false;

        if ($isSuperAdmin) {
            $site = (string) $request->query('site', '');

            return [$site === '' ? null : (int) $site, $from, $to];
        }

        return [$context->current()?->getKey(), $from, $to];
    }

    /**
     * @return array<int|string, string>
     */
    private function bakeryOptions(BakeryContext $context): array
    {
        $user = $context->user();

        if (! ($user?->hasRole(Role::SuperAdmin->value) ?? false)) {
            return [];
        }

        /** @var array<int|string, string> $options */
        $options = ['' => 'Tous les sites'] + Bakery::query()->orderBy('name')->pluck('name', 'id')->all();

        return $options;
    }
}
