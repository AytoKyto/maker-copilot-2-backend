<?php

namespace App\Tests\Entity;

use App\Entity\ViewCanalMonthProduct;
use PHPUnit\Framework\TestCase;

class ViewCanalMonthProductTest extends TestCase
{
    private ViewCanalMonthProduct $viewCanalMonthProduct;

    protected function setUp(): void
    {
        $this->viewCanalMonthProduct = new ViewCanalMonthProduct();
    }

    public function testUserId(): void
    {
        $userId = 1;
        $this->viewCanalMonthProduct->user_id = $userId;
        $this->assertEquals($userId, $this->viewCanalMonthProduct->user_id);
    }

    public function testCategoryId(): void
    {
        $categoryId = 1;
        $this->viewCanalMonthProduct->category_id = $categoryId;
        $this->assertEquals($categoryId, $this->viewCanalMonthProduct->category_id);
    }

    public function testCanalId(): void
    {
        $canalId = 1;
        $this->viewCanalMonthProduct->canal_id = $canalId;
        $this->assertEquals($canalId, $this->viewCanalMonthProduct->canal_id);
    }

    public function testName(): void
    {
        $name = 'Test Canal Product';
        $this->viewCanalMonthProduct->name = $name;
        $this->assertEquals($name, $this->viewCanalMonthProduct->name);
    }

    public function testBenefitValue(): void
    {
        $benefitValue = 100.50;
        $this->viewCanalMonthProduct->benefit_value = $benefitValue;
        $this->assertEquals($benefitValue, $this->viewCanalMonthProduct->benefit_value);
    }

    public function testPriceValue(): void
    {
        $priceValue = 500.75;
        $this->viewCanalMonthProduct->price_value = $priceValue;
        $this->assertEquals($priceValue, $this->viewCanalMonthProduct->price_value);
    }
}
