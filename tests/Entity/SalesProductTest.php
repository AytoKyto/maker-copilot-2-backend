<?php

namespace App\Tests\Entity;

use App\Entity\Client;
use App\Entity\Price;
use App\Entity\Product;
use App\Entity\Sale;
use App\Entity\SalesProduct;
use PHPUnit\Framework\TestCase;

class SalesProductTest extends TestCase
{
    private SalesProduct $salesProduct;

    protected function setUp(): void
    {
        $this->salesProduct = new SalesProduct();
    }

    public function testSale(): void
    {
        $sale = new Sale();
        $this->salesProduct->setSale($sale);
        $this->assertSame($sale, $this->salesProduct->getSale());
    }

    public function testProduct(): void
    {
        $product = new Product();
        $this->salesProduct->setProduct($product);
        $this->assertSame($product, $this->salesProduct->getProduct());
    }

    public function testPrice(): void
    {
        $price = new Price();
        $this->salesProduct->setPrice($price);
        $this->assertSame($price, $this->salesProduct->getPrice());
    }

    public function testClient(): void
    {
        $client = new Client();
        $this->salesProduct->setClient($client);
        $this->assertSame($client, $this->salesProduct->getClient());
    }
}
