<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Domain\ValueObjects;

use App\UserContext\Domain\ValueObjects\UserEmail;
use PHPUnit\Framework\TestCase;
use Assert\InvalidArgumentException;

class UserEmailTest extends TestCase
{
    public function testValidEmail(): void
    {
        $email = 'test@example.com';
        $userEmail = UserEmail::fromString($email);

        $this->assertEquals($email, (string) $userEmail);
    }

    public function testEmptyEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email should not be blank.');

        UserEmail::fromString('');
    }

    public function testInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The email "{{ value }}" is not a valid email.');

        UserEmail::fromString('invalid-email');
    }
}
