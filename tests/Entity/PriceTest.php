<?php

namespace App\Tests\Entity;

use App\Entity\Price;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class PriceTest extends TestCase
{
    private Price $price;

    protected function setUp(): void
    {
        $this->price = new Price();
    }

    public function testName(): void
    {
        $name = 'Test Price';
        $this->price->setName($name);
        $this->assertEquals($name, $this->price->getName());
    }

    public function testPrice(): void
    {
        $value = 99.99;
        $this->price->setPrice($value);
        $this->assertEquals($value, $this->price->getPrice());
    }

    public function testProduct(): void
    {
        $product = new Product();
        $this->price->setProduct($product);
        $this->assertSame($product, $this->price->getProduct());
    }

    public function testBenefit(): void
    {
        $benefit = 10.5;
        $this->price->setBenefit($benefit);
        $this->assertEquals($benefit, $this->price->getBenefit());
    }

    public function testUrsaf(): void
    {
        $ursaf = 20.5;
        $this->price->setUrsaf($ursaf);
        $this->assertEquals($ursaf, $this->price->getUrsaf());
    }

    public function testExpense(): void
    {
        $expense = 5.5;
        $this->price->setExpense($expense);
        $this->assertEquals($expense, $this->price->getExpense());
    }

    public function testCommission(): void
    {
        $commission = 2.5;
        $this->price->setCommission($commission);
        $this->assertEquals($commission, $this->price->getCommission());
    }

    public function testTime(): void
    {
        $time = 1.5;
        $this->price->setTime($time);
        $this->assertEquals($time, $this->price->getTime());
    }
}
