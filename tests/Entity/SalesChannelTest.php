<?php

namespace App\Tests\Entity;

use App\Entity\Sale;
use App\Entity\SalesChannel;
use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class SalesChannelTest extends TestCase
{
    private SalesChannel $salesChannel;

    protected function setUp(): void
    {
        $this->salesChannel = new SalesChannel();
    }

    public function testName(): void
    {
        $name = 'Test Channel';
        $this->salesChannel->setName($name);
        $this->assertEquals($name, $this->salesChannel->getName());
    }

    public function testUser(): void
    {
        $user = new User();
        $this->salesChannel->setUser($user);
        $this->assertSame($user, $this->salesChannel->getUser());
    }

    public function testSales(): void
    {
        $this->assertInstanceOf(Collection::class, $this->salesChannel->getSales());
        $this->assertEquals(0, $this->salesChannel->getSales()->count());

        $sale = new Sale();
        $this->salesChannel->addSale($sale);
        $this->assertEquals(1, $this->salesChannel->getSales()->count());
        $this->assertTrue($this->salesChannel->getSales()->contains($sale));

        $this->salesChannel->removeSale($sale);
        $this->assertEquals(0, $this->salesChannel->getSales()->count());
        $this->assertFalse($this->salesChannel->getSales()->contains($sale));
    }
}
