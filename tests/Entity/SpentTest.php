<?php

namespace App\Tests\Entity;

use App\Entity\Spent;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class SpentTest extends TestCase
{
    private Spent $spent;

    protected function setUp(): void
    {
        $this->spent = new Spent();
    }

    public function testName(): void
    {
        $name = 'Test Spent';
        $this->spent->setName($name);
        $this->assertEquals($name, $this->spent->getName());
    }

    public function testUser(): void
    {
        $user = new User();
        $this->spent->setUser($user);
        $this->assertSame($user, $this->spent->getUser());
    }
}
