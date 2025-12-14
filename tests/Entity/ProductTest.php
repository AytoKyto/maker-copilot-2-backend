<?php

namespace App\Tests\Entity;

use App\Entity\Product;
use App\Entity\Category;
use App\Entity\User;
use App\Entity\Price;
use App\Entity\SalesProduct;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

class ProductTest extends TestCase
{
    private Product $product;

    protected function setUp(): void
    {
        $this->product = new Product();
    }

    public function testName(): void
    {
        $name = 'Test Product';
        $this->product->setName($name);
        $this->assertEquals($name, $this->product->getName());
    }

    public function testImageName(): void
    {
        $imageName = 'test-image.jpg';
        $this->product->setImageName($imageName);
        $this->assertEquals($imageName, $this->product->getImageName());
    }

    public function testUser(): void
    {
        $user = new User();
        $this->product->setUser($user);
        $this->assertSame($user, $this->product->getUser());
    }

    public function testCategory(): void
    {
        $this->assertInstanceOf(Collection::class, $this->product->getCategory());
        $this->assertEquals(0, $this->product->getCategory()->count());

        $category = new Category();
        $this->product->addCategory($category);
        $this->assertEquals(1, $this->product->getCategory()->count());
        $this->assertTrue($this->product->getCategory()->contains($category));

        $this->product->removeCategory($category);
        $this->assertEquals(0, $this->product->getCategory()->count());
        $this->assertFalse($this->product->getCategory()->contains($category));
    }

    public function testPrices(): void
    {
        $this->assertInstanceOf(Collection::class, $this->product->getPrices());
        $this->assertEquals(0, $this->product->getPrices()->count());

        $price = new Price();
        $this->product->addPrice($price);
        $this->assertEquals(1, $this->product->getPrices()->count());
        $this->assertTrue($this->product->getPrices()->contains($price));

        $this->product->removePrice($price);
        $this->assertEquals(0, $this->product->getPrices()->count());
        $this->assertFalse($this->product->getPrices()->contains($price));
    }

    public function testSalesProducts(): void
    {
        $this->assertInstanceOf(Collection::class, $this->product->getSalesProducts());
        $this->assertEquals(0, $this->product->getSalesProducts()->count());

        $salesProduct = new SalesProduct();
        $this->product->addSalesProduct($salesProduct);
        $this->assertEquals(1, $this->product->getSalesProducts()->count());
        $this->assertTrue($this->product->getSalesProducts()->contains($salesProduct));

        $this->product->removeSalesProduct($salesProduct);
        $this->assertEquals(0, $this->product->getSalesProducts()->count());
        $this->assertFalse($this->product->getSalesProducts()->contains($salesProduct));
    }

    public function testIsArchived(): void
    {
        $this->product->setIsArchived(true);
        $this->assertTrue($this->product->isIsArchived());

        $this->product->setIsArchived(false);
        $this->assertFalse($this->product->isIsArchived());
    }

    public function testDates(): void
    {
        $this->product->setCreatedAtValue();
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->product->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->product->getUpdatedAt());
    }
}
