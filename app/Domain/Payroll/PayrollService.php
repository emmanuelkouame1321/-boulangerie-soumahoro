<?php

declare(strict_types=1);

namespace App\Domain\Payroll;

use App\Enums\EmployeeStatus;
use App\Enums\PayrollStatus;
use App\Exceptions\EmptyPayrollPeriodException;
use App\Exceptions\PayrollPeriodLockedException;
use App\Exceptions\PayrollPeriodNotValidatedException;
use App\Models\Bakery;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Paie (cahier §17 et §53).
 *
 * Une période est unique par site et par mois. Elle est générée depuis les
 * employés actifs (salaire de base), ajustée (primes, avances, retenues),
 * puis validée et versée. Dès la validation, l'historique est figé : une
 * période verrouillée n'est plus modifiée (cahier §53-11).
 *
 * Tous les montants sont des entiers FCFA ; le net est
 * `base + primes − avances − retenues`, jamais négatif.
 */
final class PayrollService
{
    /**
     * Ouvre (ou retourne) la période de paie d'un site pour un mois donné.
     */
    public function openPeriod(Bakery $bakery, int $year, int $month): PayrollPeriod
    {
        $month = max(1, min(12, $month));

        return DB::transaction(function () use ($bakery, $year, $month): PayrollPeriod {
            $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();

            return PayrollPeriod::withoutBakeryScope()->firstOrCreate(
                [
                    'bakery_id' => $bakery->getKey(),
                    'period_year' => $year,
                    'period_month' => $month,
                ],
                [
                    'reference' => 'PAY-'.$start->format('Ym'),
                    'started_on' => $start->toDateString(),
                    'ended_on' => $start->copy()->endOfMonth()->toDateString(),
                    'status' => PayrollStatus::Draft,
                ]
            );
        });
    }

    /**
     * Génère (ou complète) les lignes de la période depuis les employés
     * actifs du site. Les lignes déjà présentes ne sont pas écrasées.
     */
    public function generate(PayrollPeriod $period): PayrollPeriod
    {
        if ($period->isLocked()) {
            throw PayrollPeriodLockedException::for($period);
        }

        return DB::transaction(function () use ($period): PayrollPeriod {
            $employees = Employee::withoutBakeryScope()
                ->where('bakery_id', $period->bakery_id)
                ->where('status', EmployeeStatus::Active->value)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            foreach ($employees as $employee) {
                PayrollItem::withoutBakeryScope()->firstOrCreate(
                    [
                        'payroll_period_id' => $period->getKey(),
                        'employee_id' => $employee->getKey(),
                    ],
                    [
                        'bakery_id' => $period->bakery_id,
                        'base_salary' => $employee->base_salary,
                        'bonuses' => 0,
                        'advances' => 0,
                        'deductions' => 0,
                        'net_salary' => $employee->base_salary,
                    ]
                );
            }

            return $this->refreshTotals($period);
        });
    }

    /**
     * Met à jour les éléments variables d'une ligne (primes, avances,
     * retenues) et recalcule le net. Refusé si la période est figée.
     *
     * @param  array{bonuses?: int, advances?: int, deductions?: int, base_salary?: int, note?: string|null}  $data
     */
    public function updateItem(PayrollItem $item, array $data): PayrollItem
    {
        $period = $item->period;

        if ($period->isLocked()) {
            throw PayrollPeriodLockedException::for($period);
        }

        return DB::transaction(function () use ($item, $data, $period): PayrollItem {
            $item->base_salary = max(0, (int) ($data['base_salary'] ?? $item->base_salary));
            $item->bonuses = max(0, (int) ($data['bonuses'] ?? $item->bonuses));
            $item->advances = max(0, (int) ($data['advances'] ?? $item->advances));
            $item->deductions = max(0, (int) ($data['deductions'] ?? $item->deductions));

            if (array_key_exists('note', $data)) {
                $item->note = $data['note'];
            }

            $item->net_salary = self::net(
                $item->base_salary,
                $item->bonuses,
                $item->advances,
                $item->deductions
            );

            $item->save();

            $this->refreshTotals($period);

            return $item->refresh();
        });
    }

    /**
     * Valide la période : les montants sont figés et n'évolueront plus.
     */
    public function validate(PayrollPeriod $period, User $user): PayrollPeriod
    {
        if ($period->isLocked()) {
            throw PayrollPeriodLockedException::for($period);
        }

        return DB::transaction(function () use ($period, $user): PayrollPeriod {
            if ($period->items()->count() === 0) {
                throw EmptyPayrollPeriodException::create();
            }

            $this->refreshTotals($period);

            $period->status = PayrollStatus::Validated;
            $period->validated_by = $user->getKey();
            $period->validated_at = now();
            $period->save();

            return $period->refresh();
        });
    }

    /**
     * Enregistre les versements de salaire d'une période validée : une
     * ligne de paiement par employé dont le net est positif.
     */
    public function pay(
        PayrollPeriod $period,
        string $method,
        CarbonInterface $date,
        User $user,
        ?string $note = null,
    ): PayrollPeriod {
        if ($period->status !== PayrollStatus::Validated) {
            throw PayrollPeriodNotValidatedException::for($period);
        }

        return DB::transaction(function () use ($period, $method, $date, $user, $note): PayrollPeriod {
            foreach ($period->items as $item) {
                if ($item->net_salary <= 0) {
                    continue;
                }

                $item->payments()->create([
                    'bakery_id' => $period->bakery_id,
                    'employee_id' => $item->employee_id,
                    'method' => $method,
                    'amount' => $item->net_salary,
                    'paid_at' => $date->toDateString(),
                    'created_by' => $user->getKey(),
                    'note' => $note,
                ]);
            }

            $period->status = PayrollStatus::Paid;
            $period->paid_by = $user->getKey();
            $period->paid_at = now();
            $period->save();

            return $period->refresh();
        });
    }

    /**
     * Recalcule les totaux de la période depuis ses lignes.
     */
    public function refreshTotals(PayrollPeriod $period): PayrollPeriod
    {
        $items = $period->items;

        $period->total_base = (int) $items->sum('base_salary');
        $period->total_bonuses = (int) $items->sum('bonuses');
        $period->total_advances = (int) $items->sum('advances');
        $period->total_deductions = (int) $items->sum('deductions');
        $period->total_net = (int) $items->sum('net_salary');

        $period->save();

        return $period->refresh();
    }

    /**
     * Salaire net, jamais négatif.
     */
    public static function net(int $baseSalary, int $bonuses, int $advances, int $deductions): int
    {
        return max(0, $baseSalary + $bonuses - $advances - $deductions);
    }
}
