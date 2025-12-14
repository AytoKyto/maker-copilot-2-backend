<?php

namespace App\Tests\Entity;

use App\Entity\ViewCanalMonthCategory;
use PHPUnit\Framework\TestCase;

class ViewCanalMonthCategoryTest extends TestCase
{
    private ViewCanalMonthCategory $viewCanalMonthCategory;

    protected function setUp(): void
    {
        $this->viewCanalMonthCategory = new ViewCanalMonthCategory();
    }

    public function testUserId(): void
    {
        $userId = 1;
        $this->viewCanalMonthCategory->user_id = $userId;
        $this->assertEquals($userId, $this->viewCanalMonthCategory->user_id);
    }

    public function testProductId(): void
    {
        $productId = 1;
        $this->viewCanalMonthCategory->product_id = $productId;
        $this->assertEquals($productId, $this->viewCanalMonthCategory->product_id);
    }

    public function testCanalId(): void
    {
        $canalId = 1;
        $this->viewCanalMonthCategory->canal_id = $canalId;
        $this->assertEquals($canalId, $this->viewCanalMonthCategory->canal_id);
    }

    public function testName(): void
    {
        $name = 'Test Canal Category';
        $this->viewCanalMonthCategory->name = $name;
        $this->assertEquals($name, $this->viewCanalMonthCategory->name);
    }

    public function testBenefitValue(): void
    {
        $benefitValue = 100.50;
        $this->viewCanalMonthCategory->benefit_value = $benefitValue;
        $this->assertEquals($benefitValue, $this->viewCanalMonthCategory->benefit_value);
    }

    public function testPriceValue(): void
    {
        $priceValue = 500.75;
        $this->viewCanalMonthCategory->price_value = $priceValue;
        $this->assertEquals($priceValue, $this->viewCanalMonthCategory->price_value);
    }
}
