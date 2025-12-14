<?php

namespace App\Tests\Entity;

use App\Entity\Sale;
use App\Entity\User;
use App\Entity\SalesChannel;
use App\Entity\SalesProduct;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class SaleTest extends TestCase
{
    private Sale $sale;

    protected function setUp(): void
    {
        $this->sale = new Sale();
    }

    public function testName(): void
    {
        $name = 'Test Sale';
        $this->sale->setName($name);
        $this->assertEquals($name, $this->sale->getName());
    }

    public function testPrice(): void
    {
        $price = 199.99;
        $this->sale->setPrice($price);
        $this->assertEquals($price, $this->sale->getPrice());
    }

    public function testBenefit(): void
    {
        $benefit = 50.00;
        $this->sale->setBenefit($benefit);
        $this->assertEquals($benefit, $this->sale->getBenefit());
    }

    public function testNbProduct(): void
    {
        $nbProduct = 5;
        $this->sale->setNbProduct($nbProduct);
        $this->assertEquals($nbProduct, $this->sale->getNbProduct());
    }

    public function testUrsaf(): void
    {
        $ursaf = 20.5;
        $this->sale->setUrsaf($ursaf);
        $this->assertEquals($ursaf, $this->sale->getUrsaf());
    }

    public function testExpense(): void
    {
        $expense = 30.0;
        $this->sale->setExpense($expense);
        $this->assertEquals($expense, $this->sale->getExpense());
    }

    public function testCommission(): void
    {
        $commission = 10.0;
        $this->sale->setCommission($commission);
        $this->assertEquals($commission, $this->sale->getCommission());
    }

    public function testTime(): void
    {
        $time = 2.5;
        $this->sale->setTime($time);
        $this->assertEquals($time, $this->sale->getTime());
    }

    public function testUser(): void
    {
        $user = new User();
        $this->sale->setUser($user);
        $this->assertSame($user, $this->sale->getUser());
    }

    public function testCanal(): void
    {
        $canal = new SalesChannel();
        $this->sale->setCanal($canal);
        $this->assertSame($canal, $this->sale->getCanal());
    }

    public function testSalesProducts(): void
    {
        $this->assertInstanceOf(Collection::class, $this->sale->getSalesProducts());
        $this->assertEquals(0, $this->sale->getSalesProducts()->count());

        $salesProduct = new SalesProduct();
        $this->sale->addSalesProduct($salesProduct);
        $this->assertEquals(1, $this->sale->getSalesProducts()->count());
        $this->assertTrue($this->sale->getSalesProducts()->contains($salesProduct));

        $this->sale->removeSalesProduct($salesProduct);
        $this->assertEquals(0, $this->sale->getSalesProducts()->count());
        $this->assertFalse($this->sale->getSalesProducts()->contains($salesProduct));
    }

    public function testDates(): void
    {
        $this->sale->setCreatedAtValue();
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->sale->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->sale->getUpdatedAt());
    }
}
