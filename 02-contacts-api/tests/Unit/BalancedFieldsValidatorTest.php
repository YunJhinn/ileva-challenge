<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\ValidationException;
use App\Support\Validator;
use PHPUnit\Framework\TestCase;

final class BalancedFieldsValidatorTest extends TestCase
{
    public function testPersonAcceptsAValidName(): void
    {
        $result = Validator::person(['name' => '  Grace Hopper  ']);

        $this->assertSame('Grace Hopper', $result['name']);
    }

    public function testPersonRejectsAnEmptyName(): void
    {
        $this->expectException(ValidationException::class);

        Validator::person(['name' => '   ']);
    }

    public function testContactAcceptsAValidEmail(): void
    {
        $result = Validator::contact(['type' => 'email', 'value' => 'grace@example.com']);

        $this->assertSame('email', $result['type']);
        $this->assertSame('grace@example.com', $result['value']);
    }

    public function testContactRejectsAMalformedEmail(): void
    {
        $this->expectException(ValidationException::class);

        Validator::contact(['type' => 'email', 'value' => 'not-an-email']);
    }

    public function testContactRejectsAnUnknownType(): void
    {
        $this->expectException(ValidationException::class);

        Validator::contact(['type' => 'fax', 'value' => '123']);
    }
}
