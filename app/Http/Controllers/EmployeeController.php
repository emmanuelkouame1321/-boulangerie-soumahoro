<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Sites\BakeryContext;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Bakery;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Employés (cahier §16).
 *
 * Contrôleur mince : autorisation par EmployeePolicy, validation par Form
 * Request. Le poste et le type de contrat proviennent de la configuration.
 */
class EmployeeController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Employee::class);

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $employees = Employee::query()
            ->with('bakery')
            ->search($search)
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'search' => $search,
            'status' => $status,
            'statusOptions' => [''] + (array) config('boulangerie.employee_statuses'),
            'activeCount' => Employee::query()->active()->count(),
            'canManage' => $request->user()?->can('create', Employee::class) ?? false,
        ]);
    }

    public function create(BakeryContext $context): View
    {
        $this->authorize('create', Employee::class);

        return view('employees.create', $this->formData($context));
    }

    public function store(StoreEmployeeRequest $request, BakeryContext $context): RedirectResponse
    {
        $this->authorize('create', Employee::class);

        $bakery = $this->resolveBakery($context);

        if ($bakery === null) {
            return back()->withInput()->with('error', 'Sélectionnez une boulangerie avant de créer l’employé.');
        }

        $employee = Employee::create($this->attributes($request->validated(), $bakery));

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', "L’employé « {$employee->fullName()} » a été créé.");
    }

    public function show(Employee $employee): View
    {
        $this->authorize('view', Employee::class);

        $employee->load(['contracts', 'payrollItems.period', 'payrollPayments.item']);

        return view('employees.show', [
            'employee' => $employee,
        ]);
    }

    public function edit(Employee $employee, BakeryContext $context): View
    {
        $this->authorize('update', Employee::class);

        return view('employees.edit', array_merge($this->formData($context), [
            'employee' => $employee,
        ]));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', Employee::class);

        $data = $request->validated();

        $employee->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'position' => $data['position'],
            'contract_type' => $data['contract_type'],
            'base_salary' => (int) $data['base_salary'],
            'hired_on' => $data['hired_on'] ?? null,
            'status' => $data['status'],
            'user_id' => $data['user_id'] ?? null,
            'admin_note' => $data['admin_note'] ?? null,
        ])->save();

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', "L’employé « {$employee->fullName()} » a été mis à jour.");
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, Bakery $bakery): array
    {
        return [
            'bakery_id' => $bakery->getKey(),
            'user_id' => $data['user_id'] ?? null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'position' => $data['position'],
            'contract_type' => $data['contract_type'],
            'base_salary' => (int) $data['base_salary'],
            'hired_on' => $data['hired_on'] ?? null,
            'status' => $data['status'],
            'admin_note' => $data['admin_note'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(BakeryContext $context): array
    {
        return [
            'bakeryOptions' => $context->availableBakeries()->pluck('name', 'id')->all(),
            'currentBakeryId' => $context->current()?->getKey(),
            'positions' => (array) config('boulangerie.positions'),
            'contractTypes' => (array) config('boulangerie.contract_types'),
            'statuses' => (array) config('boulangerie.employee_statuses'),
            'employee' => null,
        ];
    }

    private function resolveBakery(BakeryContext $context): ?Bakery
    {
        return $context->current() ?? $context->availableBakeries()->first();
    }
}
