<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Type de contrat d'un employé (cahier §16.1).
 */
enum ContractType: string
{
    case Cdi = 'cdi';
    case Cdd = 'cdd';
    case Intern = 'intern';
    case Daily = 'daily';

    public function label(): string
    {
        return (string) config(
            "boulangerie.contract_types.{$this->value}",
            Str::upper($this->value)
        );
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
