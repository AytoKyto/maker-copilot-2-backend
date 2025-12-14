<?php

namespace App\Tests\Entity;

use App\Entity\ViewCanalYearProduct;
use PHPUnit\Framework\TestCase;

class ViewCanalYearProductTest extends TestCase
{
    private ViewCanalYearProduct $viewCanalYearProduct;

    protected function setUp(): void
    {
        $this->viewCanalYearProduct = new ViewCanalYearProduct();
    }

    public function testUserId(): void
    {
        $userId = 1;
        $this->viewCanalYearProduct->user_id = $userId;
        $this->assertEquals($userId, $this->viewCanalYearProduct->user_id);
    }

    public function testProductId(): void
    {
        $productId = 1;
        $this->viewCanalYearProduct->product_id = $productId;
        $this->assertEquals($productId, $this->viewCanalYearProduct->product_id);
    }

    public function testCanalId(): void
    {
        $canalId = 1;
        $this->viewCanalYearProduct->canal_id = $canalId;
        $this->assertEquals($canalId, $this->viewCanalYearProduct->canal_id);
    }

    public function testName(): void
    {
        $name = 'Test Canal Year Product';
        $this->viewCanalYearProduct->name = $name;
        $this->assertEquals($name, $this->viewCanalYearProduct->name);
    }

    public function testBenefitValue(): void
    {
        $benefitValue = 100.50;
        $this->viewCanalYearProduct->benefit_value = $benefitValue;
        $this->assertEquals($benefitValue, $this->viewCanalYearProduct->benefit_value);
    }

    public function testPriceValue(): void
    {
        $priceValue = 500.75;
        $this->viewCanalYearProduct->price_value = $priceValue;
        $this->assertEquals($priceValue, $this->viewCanalYearProduct->price_value);
    }
}
