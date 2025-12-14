<?php

namespace App\Tests\Entity;

use App\Entity\Client;
use App\Entity\User;
use App\Entity\SalesProduct;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $this->client = new Client();
    }

    public function testName(): void
    {
        $name = 'Test Client';
        $this->client->setName($name);
        $this->assertEquals($name, $this->client->getName());
    }

    public function testUser(): void
    {
        $user = new User();
        $this->client->setUser($user);
        $this->assertSame($user, $this->client->getUser());
    }

    public function testSalesProducts(): void
    {
        $this->assertInstanceOf(Collection::class, $this->client->getSalesProducts());
        $this->assertEquals(0, $this->client->getSalesProducts()->count());

        $salesProduct = new SalesProduct();
        $this->client->addSalesProduct($salesProduct);
        $this->assertEquals(1, $this->client->getSalesProducts()->count());
        $this->assertTrue($this->client->getSalesProducts()->contains($salesProduct));

        $this->client->removeSalesProduct($salesProduct);
        $this->assertEquals(0, $this->client->getSalesProducts()->count());
        $this->assertFalse($this->client->getSalesProducts()->contains($salesProduct));
    }
}
