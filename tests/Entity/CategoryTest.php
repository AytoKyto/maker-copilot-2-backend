<?php

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    private Category $category;

    protected function setUp(): void
    {
        $this->category = new Category();
    }

    public function testName(): void
    {
        $name = 'Test Category';
        $this->category->setName($name);
        $this->assertEquals($name, $this->category->getName());
    }

    public function testUser(): void
    {
        $user = new User();
        $this->category->setUser($user);
        $this->assertSame($user, $this->category->getUser());
    }

    public function testProducts(): void
    {
        $this->assertInstanceOf(Collection::class, $this->category->getProducts());
        $this->assertEquals(0, $this->category->getProducts()->count());

        $product = new Product();
        $this->category->addProduct($product);
        $this->assertEquals(1, $this->category->getProducts()->count());
        $this->assertTrue($this->category->getProducts()->contains($product));

        $this->category->removeProduct($product);
        $this->assertEquals(0, $this->category->getProducts()->count());
        $this->assertFalse($this->category->getProducts()->contains($product));
    }
}
