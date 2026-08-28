<?php

declare(strict_types=1);

namespace IlevaChallenge\BalancedBrackets;

/**
 * Determines whether a string of brackets is valid.
 *
 * A string is valid when:
 *  - every opening bracket has a matching closing bracket of the same type;
 *  - brackets close in the correct (LIFO) order;
 *  - there are no unmatched brackets left over.
 *
 * Any character that is not one of ()[]{} is ignored, so the function also
 * works fine on strings that mix brackets with other text.
 */
final class BalancedBrackets
{
    /** @var array<string, string> Maps a closing bracket to its opening counterpart. */
    private const PAIRS = [
        ')' => '(',
        ']' => '[',
        '}' => '{',
    ];

    private const OPENERS = ['(', '[', '{'];

    public static function isValid(string $input): bool
    {
        /** @var string[] $stack */
        $stack = [];

        $length = mb_strlen($input);
        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($input, $i, 1);

            if (in_array($char, self::OPENERS, true)) {
                $stack[] = $char;
                continue;
            }

            if (array_key_exists($char, self::PAIRS)) {
                $expectedOpener = array_pop($stack);

                if ($expectedOpener !== self::PAIRS[$char]) {
                    // Either the stack was empty (nothing to pop, null !== opener)
                    // or the top of the stack doesn't match this closer.
                    return false;
                }
            }

            // Any other character is simply ignored.
        }

        // Valid only if every opener found a matching closer.
        return $stack === [];
    }
}
