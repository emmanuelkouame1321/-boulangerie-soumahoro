<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Grand livre de stock (cahier §3.2).
 *
 * Toute variation de stock passe par un mouvement de ce type.
 * Aucun mouvement n'est supprimé physiquement : une correction est un
 * mouvement compensatoire (cahier §3.3).
 */
enum StockMovementType: string
{
    case SupplierIn = 'supplier_in';
    case ProductionOut = 'production_out';
    case ProductionIn = 'production_in';
    case SaleOut = 'sale_out';
    case LossOut = 'loss_out';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case SupplierReturn = 'supplier_return';
    case CustomerReturn = 'customer_return';
    case AdminCorrection = 'admin_correction';

    public function label(): string
    {
        return (string) config("boulangerie.stock_movement_types.{$this->value}", $this->value);
    }

    /**
     * Sens du mouvement dans le grand livre.
     */
    public function sign(): int
    {
        return match ($this) {
            self::SupplierIn,
            self::ProductionIn,
            self::AdjustmentIn,
            self::TransferIn,
            self::CustomerReturn => 1,
            default => -1,
        };
    }

    public function increasesStock(): bool
    {
        return $this->sign() === 1;
    }

    /**
     * Les mouvements critiques nécessitent une clé d'idempotence (cahier §29).
     */
    public function isCritical(): bool
    {
        return match ($this) {
            self::SupplierIn,
            self::ProductionIn,
            self::ProductionOut,
            self::SaleOut,
            self::LossOut,
            self::TransferIn,
            self::TransferOut => true,
            default => false,
        };
    }
}
