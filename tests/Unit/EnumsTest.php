<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Role;
use App\Enums\StockMovementType;
use Tests\TestCase;

class EnumsTest extends TestCase
{
    public function test_super_admin_is_the_only_role_with_the_wildcard(): void
    {
        $this->assertSame(['*'], Role::SuperAdmin->permissions());

        foreach ([Role::ProductionManager, Role::SalesManager] as $role) {
            $this->assertNotContains('*', $role->permissions());
        }
    }

    public function test_stock_movement_direction_is_consistent(): void
    {
        $this->assertTrue(StockMovementType::SupplierIn->increasesStock());
        $this->assertTrue(StockMovementType::ProductionIn->increasesStock());
        $this->assertFalse(StockMovementType::SaleOut->increasesStock());
        $this->assertFalse(StockMovementType::LossOut->increasesStock());
    }

    public function test_critical_movements_are_flagged_for_idempotency(): void
    {
        $this->assertTrue(StockMovementType::ProductionOut->isCritical());
        $this->assertTrue(StockMovementType::SupplierIn->isCritical());
        $this->assertFalse(StockMovementType::AdminCorrection->isCritical());
    }
}
