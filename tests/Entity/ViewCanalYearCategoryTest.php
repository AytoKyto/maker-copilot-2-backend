<?php

namespace App\Tests\Entity;

use App\Entity\ViewCanalYearCategory;
use PHPUnit\Framework\TestCase;

class ViewCanalYearCategoryTest extends TestCase
{
    private ViewCanalYearCategory $viewCanalYearCategory;

    protected function setUp(): void
    {
        $this->viewCanalYearCategory = new ViewCanalYearCategory();
    }

    public function testUserId(): void
    {
        $userId = 1;
        $this->viewCanalYearCategory->user_id = $userId;
        $this->assertEquals($userId, $this->viewCanalYearCategory->user_id);
    }

    public function testCategoryId(): void
    {
        $categoryId = 1;
        $this->viewCanalYearCategory->category_id = $categoryId;
        $this->assertEquals($categoryId, $this->viewCanalYearCategory->category_id);
    }

    public function testCanalId(): void
    {
        $canalId = 1;
        $this->viewCanalYearCategory->canal_id = $canalId;
        $this->assertEquals($canalId, $this->viewCanalYearCategory->canal_id);
    }

    public function testName(): void
    {
        $name = 'Test Canal Year Category';
        $this->viewCanalYearCategory->name = $name;
        $this->assertEquals($name, $this->viewCanalYearCategory->name);
    }

    public function testBenefitValue(): void
    {
        $benefitValue = 100.50;
        $this->viewCanalYearCategory->benefit_value = $benefitValue;
        $this->assertEquals($benefitValue, $this->viewCanalYearCategory->benefit_value);
    }

    public function testPriceValue(): void
    {
        $priceValue = 500.75;
        $this->viewCanalYearCategory->price_value = $priceValue;
        $this->assertEquals($priceValue, $this->viewCanalYearCategory->price_value);
    }
}
