<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\Collection;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testEmail(): void
    {
        $email = 'test@example.com';
        $this->user->setEmail($email);
        $this->assertEquals($email, $this->user->getEmail());
    }

    public function testRoles(): void
    {
        $roles = ['ROLE_USER', 'ROLE_ADMIN'];
        $this->user->setRoles($roles);
        $this->assertContains('ROLE_USER', $this->user->getRoles());
        $this->assertContains('ROLE_ADMIN', $this->user->getRoles());
    }

    public function testPassword(): void
    {
        $password = 'password123';
        $this->user->setPassword($password);
        $this->assertEquals($password, $this->user->getPassword());
    }

    public function testUrssafPourcent(): void
    {
        $pourcent = 22.5;
        $this->user->setUrssafPourcent($pourcent);
        $this->assertEquals($pourcent, $this->user->getUrssafPourcent());
    }

    public function testUrssafType(): void
    {
        $type = 1;
        $this->user->setUrssafType($type);
        $this->assertEquals($type, $this->user->getUrssafType());
    }

    public function testCollections(): void
    {
        $this->assertInstanceOf(Collection::class, $this->user->getProducts());
        $this->assertInstanceOf(Collection::class, $this->user->getCategories());
        $this->assertInstanceOf(Collection::class, $this->user->getSalesChannels());
        $this->assertInstanceOf(Collection::class, $this->user->getClients());
        $this->assertInstanceOf(Collection::class, $this->user->getSales());
        $this->assertInstanceOf(Collection::class, $this->user->getSpents());
    }

    public function testObjectifValue(): void
    {
        $value = 1000;
        $this->user->setObjectifValue($value);
        $this->assertEquals($value, $this->user->getObjectifValue());
    }

    public function testTypeSubscription(): void
    {
        $type = 1;
        $this->user->setTypeSubscription($type);
        $this->assertEquals($type, $this->user->getTypeSubscription());
    }

    public function testDates(): void
    {
        $date = new \DateTimeImmutable();
        
        $this->user->setCreatedAt($date);
        $this->assertEquals($date, $this->user->getCreatedAt());

        $this->user->setUpdatedAt($date);
        $this->assertEquals($date, $this->user->getUpdatedAt());
    }
}
