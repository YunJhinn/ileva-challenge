<?php

declare(strict_types=1);

namespace IlevaChallenge\BalancedBrackets\Tests;

use IlevaChallenge\BalancedBrackets\BalancedBrackets;
use PHPUnit\Framework\TestCase;

final class BalancedBracketsTest extends TestCase
{
    /**
     * @dataProvider validExamples
     */
    public function testValidExamples(string $input): void
    {
        $this->assertTrue(BalancedBrackets::isValid($input), "Expected '{$input}' to be valid");
    }

    /**
     * @dataProvider invalidExamples
     */
    public function testInvalidExamples(string $input): void
    {
        $this->assertFalse(BalancedBrackets::isValid($input), "Expected '{$input}' to be invalid");
    }

    public static function validExamples(): array
    {
        return [
            'from the brief: ()[]{}' => ['(){}[]'],
            'from the brief: nested' => ['[{()}](){}'],
            'empty string' => [''],
            'no brackets at all' => ['hello world'],
            'brackets mixed with text' => ['function f(a, b) { return [a, b]; }'],
        ];
    }

    public static function invalidExamples(): array
    {
        return [
            'from the brief: unclosed []' => ['[]{()'],
            'from the brief: wrong order' => ['[{)]'],
            'single opener' => ['('],
            'single closer' => [')'],
            'closer before opener' => [')('],
            'mismatched types' => ['(]'],
        ];
    }
}
