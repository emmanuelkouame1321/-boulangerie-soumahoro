<?php

declare(strict_types=1);

namespace Tests\Feature\Measurement;

use App\Domain\Measurement\UnitConversionService;
use App\Exceptions\UnitConversionException;
use App\Models\Unit;
use Database\Seeders\UnitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitConversionServiceTest extends TestCase
{
    use RefreshDatabase;

    private UnitConversionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitsSeeder::class);
        $this->service = new UnitConversionService;
    }

    private function unit(string $code): Unit
    {
        return Unit::query()->where('code', $code)->firstOrFail();
    }

    public function test_one_sack_equals_fifty_kilograms(): void
    {
        $this->assertSame('50', $this->service->factor($this->unit('sack'), $this->unit('kg')));
        $this->assertSame('50', $this->service->convert(1, $this->unit('sack'), $this->unit('kg')));
        $this->assertSame('100', $this->service->convert(2, $this->unit('sack'), $this->unit('kg')));
    }

    public function test_conversion_is_reversible(): void
    {
        // 500 g = 0,5 kg
        $this->assertSame('0.5', $this->service->convert(500, $this->unit('g'), $this->unit('kg')));
    }

    public function test_conversion_chains_through_intermediate_units(): void
    {
        // 1 sac = 50 kg = 50 000 g
        $this->assertSame('50000', $this->service->convert(1, $this->unit('sack'), $this->unit('g')));
    }

    public function test_same_unit_is_an_identity(): void
    {
        $this->assertSame('1', $this->service->factor($this->unit('kg'), $this->unit('kg')));
        $this->assertSame('12', $this->service->convert(12, $this->unit('kg'), $this->unit('kg')));
    }

    public function test_impossible_conversion_is_reported(): void
    {
        $this->assertFalse($this->service->canConvert($this->unit('piece'), $this->unit('kg')));

        $this->expectException(UnitConversionException::class);

        $this->service->convert(1, $this->unit('piece'), $this->unit('kg'));
    }
}
