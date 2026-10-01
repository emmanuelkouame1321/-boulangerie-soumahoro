<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Expenses\ExpenseService;
use App\Domain\Sites\BakeryContext;
use App\Http\Requests\StoreExpenseCategoryRequest;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Bakery;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Charges d'exploitation (cahier §18).
 *
 * Contrôleur mince : autorisation par ExpensePolicy, validation par Form
 * Request, enregistrement par ExpenseService. Les catégories sont
 * administrables et se désactivent (jamais de suppression).
 */
class ExpenseController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly ExpenseService $expenses,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Expense::class);

        $search = trim((string) $request->query('search', ''));
        $from = (string) $request->query('from', now()->startOfMonth()->toDateString());
        $to = (string) $request->query('to', now()->toDateString());
        $category = (string) $request->query('category', '');

        $expenses = Expense::query()
            ->with(['category', 'bakery'])
            ->search($search)
            ->between($from, $to)
            ->when($category !== '', fn ($query) => $query->where('expense_category_id', (int) $category))
            ->orderByDesc('spent_on')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('expenses.index', [
            'expenses' => $expenses,
            'search' => $search,
            'from' => $from,
            'to' => $to,
            'category' => $category,
            'categories' => ExpenseCategory::query()->withCount('expenses')->ordered()->get(),
            'categoryOptions' => ['' => 'Toutes'] + ExpenseCategory::query()->ordered()->pluck('name', 'id')->all(),
            'total' => (int) Expense::query()->between($from, $to)->sum('amount'),
            'monthTotal' => (int) Expense::query()
                ->whereYear('spent_on', now()->year)
                ->whereMonth('spent_on', now()->month)
                ->sum('amount'),
            'canManage' => $request->user()?->can('create', Expense::class) ?? false,
        ]);
    }

    public function create(BakeryContext $context): View
    {
        $this->authorize('create', Expense::class);

        return view('expenses.create', [
            'expense' => null,
            'categoryOptions' => ExpenseCategory::query()->active()->ordered()->pluck('name', 'id')->all(),
            'paymentMethods' => PaymentMethod::query()->active()->ordered()->pluck('label', 'code')->all(),
            'currentBakeryId' => $context->current()?->getKey(),
        ]);
    }

    public function store(StoreExpenseRequest $request, BakeryContext $context): RedirectResponse
    {
        $this->authorize('create', Expense::class);

        $bakery = $this->resolveBakery($context);

        if ($bakery === null) {
            return back()->withInput()->with('error', 'Sélectionnez une boulangerie avant d’enregistrer la charge.');
        }

        $user = $request->user();

        $this->expenses->record($bakery, $request->validated(), $user instanceof User ? $user : null);

        return redirect()
            ->route('expenses.index')
            ->with('status', 'La charge a été enregistrée.');
    }

    public function edit(Expense $expense): View
    {
        $this->authorize('update', Expense::class);

        return view('expenses.edit', [
            'expense' => $expense,
            'categoryOptions' => ExpenseCategory::query()->active()->ordered()->pluck('name', 'id')->all(),
            'paymentMethods' => PaymentMethod::query()->active()->ordered()->pluck('label', 'code')->all(),
            'currentBakeryId' => $expense->bakery_id,
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', Expense::class);

        $data = $request->validated();

        $expense->fill([
            'expense_category_id' => $data['expense_category_id'] ?? null,
            'label' => $data['label'] ?? null,
            'amount' => (int) $data['amount'],
            'spent_on' => $data['spent_on'],
            'payee' => $data['payee'] ?? null,
            'method' => $data['method'] ?? $expense->method,
            'comment' => $data['comment'] ?? null,
        ])->save();

        return redirect()
            ->route('expenses.index')
            ->with('status', 'La charge a été mise à jour.');
    }

    public function storeCategory(StoreExpenseCategoryRequest $request, BakeryContext $context): RedirectResponse
    {
        $this->authorize('create', Expense::class);

        $bakery = $this->resolveBakery($context);

        if ($bakery === null) {
            return back()->with('error', 'Sélectionnez une boulangerie avant d’ajouter une catégorie.');
        }

        $data = $request->validated();

        $this->expenses->createCategory(
            $bakery,
            (string) $data['code'],
            (string) $data['name'],
            (int) ($data['position'] ?? 0),
        );

        return back()->with('status', 'La catégorie a été ajoutée.');
    }

    public function deactivateCategory(ExpenseCategory $category): RedirectResponse
    {
        $this->authorize('update', Expense::class);

        $this->expenses->deactivateCategory($category);

        return back()->with('status', 'La catégorie a été désactivée.');
    }

    private function resolveBakery(BakeryContext $context): ?Bakery
    {
        return $context->current() ?? $context->availableBakeries()->first();
    }
}
