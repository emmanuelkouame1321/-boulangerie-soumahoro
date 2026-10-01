<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Statut d'un employé (cahier §16.1).
 */
enum EmployeeStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    public function label(): string
    {
        return (string) config(
            "boulangerie.statuses.employee.{$this->value}.label",
            (string) config("boulangerie.employee_statuses.{$this->value}", Str::headline($this->value))
        );
    }

    public function tone(): string
    {
        return (string) config("boulangerie.statuses.employee.{$this->value}.tone", 'neutral');
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
