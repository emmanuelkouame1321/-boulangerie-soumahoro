<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Payroll\PayrollService;
use App\Domain\Sites\BakeryContext;
use App\Exceptions\EmptyPayrollPeriodException;
use App\Exceptions\PayrollPeriodLockedException;
use App\Exceptions\PayrollPeriodNotValidatedException;
use App\Http\Requests\PayPayrollRequest;
use App\Http\Requests\UpdatePayrollItemRequest;
use App\Models\PaymentMethod;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Paie (cahier §17).
 *
 * Contrôleur mince : ouverture de période, génération, ajustement des
 * lignes, validation et versement. Toute la logique vit dans
 * PayrollService ; les états figés sont renvoyés en message clair.
 */
class PayrollController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly PayrollService $payroll,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PayrollPeriod::class);

        $year = (int) $request->query('year', (string) now()->year);

        $periods = PayrollPeriod::query()
            ->withCount('items')
            ->forYear($year)
            ->orderByDesc('period_month')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('payroll.index', [
            'periods' => $periods,
            'year' => $year,
            'totalNet' => (int) PayrollPeriod::query()->forYear($year)->sum('total_net'),
            'periodCount' => PayrollPeriod::query()->forYear($year)->count(),
            'canManage' => $request->user()?->can('create', PayrollPeriod::class) ?? false,
        ]);
    }

    public function storePeriod(Request $request, BakeryContext $context): RedirectResponse
    {
        $this->authorize('create', PayrollPeriod::class);

        $data = $request->validate([
            'period_year' => ['required', 'integer', 'between:2000,2100'],
            'period_month' => ['required', 'integer', 'between:1,12'],
        ]);

        $bakery = $context->current() ?? $context->availableBakeries()->first();

        if ($bakery === null) {
            return back()->with('error', 'Sélectionnez une boulangerie avant d’ouvrir une période.');
        }

        $period = $this->payroll->openPeriod($bakery, (int) $data['period_year'], (int) $data['period_month']);
        $this->payroll->generate($period);

        return redirect()
            ->route('payroll.show', $period)
            ->with('status', "La période {$period->monthLabel()} a été préparée.");
    }

    public function show(Request $request, PayrollPeriod $period): View
    {
        $this->authorize('view', PayrollPeriod::class);

        $period->load(['items.employee', 'items.payments', 'validatedBy', 'paidBy', 'payments']);

        return view('payroll.show', [
            'period' => $period,
            'paymentMethods' => PaymentMethod::query()->active()->ordered()->pluck('label', 'code')->all(),
            'canManage' => $request->user()?->can('update', PayrollPeriod::class) ?? false,
        ]);
    }

    public function generate(PayrollPeriod $period): RedirectResponse
    {
        $this->authorize('update', PayrollPeriod::class);

        try {
            $this->payroll->generate($period);
        } catch (PayrollPeriodLockedException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Les lignes de paie ont été générées.');
    }

    public function updateItem(UpdatePayrollItemRequest $request, PayrollItem $item): RedirectResponse
    {
        $this->authorize('update', PayrollPeriod::class);

        try {
            $this->payroll->updateItem($item, $request->validated());
        } catch (PayrollPeriodLockedException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'La ligne de paie a été mise à jour.');
    }

    public function validatePeriod(PayrollPeriod $period, Request $request): RedirectResponse
    {
        $this->authorize('validate', PayrollPeriod::class);

        $user = $request->user();

        if (! $user instanceof User) {
            return back()->with('error', 'Utilisateur introuvable.');
        }

        try {
            $this->payroll->validate($period, $user);
        } catch (PayrollPeriodLockedException|EmptyPayrollPeriodException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', "La période {$period->monthLabel()} a été validée.");
    }

    public function pay(PayPayrollRequest $request, PayrollPeriod $period): RedirectResponse
    {
        $this->authorize('pay', PayrollPeriod::class);

        $user = $request->user();

        if (! $user instanceof User) {
            return back()->with('error', 'Utilisateur introuvable.');
        }

        $data = $request->validated();

        try {
            $this->payroll->pay(
                $period,
                (string) $data['method'],
                Carbon::parse((string) $data['paid_at']),
                $user,
                $data['note'] ?? null,
            );
        } catch (PayrollPeriodNotValidatedException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', "Les salaires de {$period->monthLabel()} ont été versés.");
    }
}
