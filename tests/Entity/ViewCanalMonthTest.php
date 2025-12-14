<?php

namespace App\Tests\Entity;

use App\Entity\ViewCanalMonth;
use PHPUnit\Framework\TestCase;

class ViewCanalMonthTest extends TestCase
{
    private ViewCanalMonth $viewCanalMonth;

    protected function setUp(): void
    {
        $this->viewCanalMonth = new ViewCanalMonth();
    }

    public function testUserId(): void
    {
        $userId = 1;
        $this->viewCanalMonth->user_id = $userId;
        $this->assertEquals($userId, $this->viewCanalMonth->user_id);
    }

    public function testCanalId(): void
    {
        $canalId = 1;
        $this->viewCanalMonth->canal_id = $canalId;
        $this->assertEquals($canalId, $this->viewCanalMonth->canal_id);
    }

    public function testName(): void
    {
        $name = 'Test Canal';
        $this->viewCanalMonth->name = $name;
        $this->assertEquals($name, $this->viewCanalMonth->name);
    }

    public function testBenefitValue(): void
    {
        $benefitValue = 100.50;
        $this->viewCanalMonth->benefit_value = $benefitValue;
        $this->assertEquals($benefitValue, $this->viewCanalMonth->benefit_value);
    }

    public function testPriceValue(): void
    {
        $priceValue = 500.75;
        $this->viewCanalMonth->price_value = $priceValue;
        $this->assertEquals($priceValue, $this->viewCanalMonth->price_value);
    }

    public function testNbProductValue(): void
    {
        $nbProductValue = 5;
        $this->viewCanalMonth->nb_product_value = $nbProductValue;
        $this->assertEquals($nbProductValue, $this->viewCanalMonth->nb_product_value);
    }
}
