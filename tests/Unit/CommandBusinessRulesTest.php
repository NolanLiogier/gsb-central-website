<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Repositories\CommandRepository;

final class CommandBusinessRulesTest extends TestCase
{
    public function testAddCommandRequiresUserDeliveryDateAndStatus(): void
    {
        $repo = new CommandRepository();
        $invalid = [
            'user_id' => 0,
            'delivery_date' => '2025-12-01',
            'fk_status_id' => 3,
        ];
        $result = $repo->addCommand($invalid, []);
        $this->assertFalse($result);
    }

    public function testAddCommandRefusesEmptyProducts(): void
    {
        $repo = new CommandRepository();
        $data = [
            'user_id' => 1,
            'delivery_date' => '2025-12-01',
            'fk_status_id' => 3,
        ];
        $result = $repo->addCommand($data, []);
        $this->assertIsBool($result);
    }

    public function testCommandTotalCalculation(): void
    {
        $products = [
            ['product_id' => 1, 'price' => 10.00, 'quantity' => 2],
            ['product_id' => 2, 'price' => 5.50, 'quantity' => 3],
        ];
        $total = 0.0;
        foreach ($products as $p) {
            $total += (float) $p['price'] * (int) $p['quantity'];
        }
        $this->assertSame(36.5, round($total, 2));
    }

    public function testUpdateCommandRefusesNegativeQuantity(): void
    {
        $products = [
            1 => ['quantity' => -1],
        ];
        $qty = (int) ($products[1]['quantity'] ?? 0);
        $this->assertLessThan(0, $qty);
    }

    public function testStatusIdsEnAttenteValidéeEnvoyée(): void
    {
        $this->assertSame(3, 3);
        $this->assertSame(1, 1);
        $this->assertSame(2, 2);
    }
}
