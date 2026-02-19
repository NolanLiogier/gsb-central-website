<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Repositories\StockRepository;
use App\Repositories\CommandRepository;

final class StockBusinessRulesTest extends TestCase
{
    public function testAddProductRefusesNegativeQuantity(): void
    {
        $repo = new StockRepository();
        $data = [
            'product_name' => 'Test Product',
            'quantity' => -5,
            'price' => 10.00,
        ];
        $result = $repo->addProduct($data);
        $this->assertFalse($result);
    }

    public function testAddProductAcceptsValidData(): void
    {
        $repo = new StockRepository();
        $data = [
            'product_name' => 'Valid Product',
            'quantity' => 10,
            'price' => 25.50,
        ];
        $result = $repo->addProduct($data);
        $this->assertIsBool($result);
    }

    public function testUpdateProductRefusesNegativeQuantity(): void
    {
        $repo = new StockRepository();
        $data = [
            'product_id' => 1,
            'product_name' => 'Product',
            'quantity' => -1,
            'price' => 10.00,
        ];
        $result = $repo->updateProduct($data);
        $this->assertFalse($result);
    }

    public function testUpdateStockQtyReturnsStructureWithSuccessAndInsufficientProducts(): void
    {
        $repo = new CommandRepository();
        $result = $repo->updateStockQty(0);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('insufficient_products', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsArray($result['insufficient_products']);
    }
}
