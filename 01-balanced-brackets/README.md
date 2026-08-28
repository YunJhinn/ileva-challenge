# 1. Suportes balanceados / Balanced Brackets

Two equivalent implementations of the same algorithm, one in PHP and one in JavaScript, so both languages requested in the brief are covered.

## Approach

Walk the string once, left to right, using a stack:

- an opening bracket (`(`, `[`, `{`) is pushed onto the stack;
- a closing bracket (`)`, `]`, `}`) must match the bracket on top of the stack — if the stack is empty or the top doesn't match, the string is invalid immediately;
- any other character is ignored, so the function also works on strings that mix brackets with other text (useful if you ever want to validate brackets inside real source code).

At the end, the string is valid only if the stack is empty (every opener found its closer).

This is O(n) time and O(n) space, and is the standard/expected solution for this class of problem.

## Run it

**PHP** (no dependencies needed to just run it manually):

```bash
php -r "require 'php/BalancedBrackets.php'; var_dump(\IlevaChallenge\BalancedBrackets\BalancedBrackets::isValid('(){}[]'));"
```

Tests (PHPUnit, installed via the root `composer.json`... see below):

```bash
cd 01-balanced-brackets/php
composer install
vendor/bin/phpunit BalancedBracketsTest.php
```

**JavaScript**:

```bash
cd 01-balanced-brackets/js
npm install
npm test
```

## Examples from the brief

| Input | Valid? |
|---|---|
| `(){}[]` | ✅ |
| `[{()}](){}` | ✅ |
| `[]{()` | ❌ |
| `[{)]` | ❌ |
