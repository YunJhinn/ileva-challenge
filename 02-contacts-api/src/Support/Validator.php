<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Contact;

/**
 * Deliberately tiny, hand-rolled validation (no extra dependency needed
 * for the two shapes of input this API accepts).
 */
final class Validator
{
    /**
     * @param array<string, mixed> $data
     * @return array{name: string}
     */
    public static function person(array $data): array
    {
        $errors = [];
        $name = is_string($data['name'] ?? null) ? trim($data['name']) : '';

        if ($name === '') {
            $errors['name'] = 'name is required and must not be empty';
        } elseif (mb_strlen($name) > 255) {
            $errors['name'] = 'name must be at most 255 characters';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return ['name' => $name];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{type: string, value: string}
     */
    public static function contact(array $data): array
    {
        $errors = [];

        $type = is_string($data['type'] ?? null) ? trim($data['type']) : '';
        $value = is_string($data['value'] ?? null) ? trim($data['value']) : '';

        if (!in_array($type, Contact::TYPES, true)) {
            $errors['type'] = 'type must be one of: ' . implode(', ', Contact::TYPES);
        }

        if ($value === '') {
            $errors['value'] = 'value is required and must not be empty';
        } elseif ($type === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors['value'] = 'value must be a valid email address';
        } elseif (mb_strlen($value) > 255) {
            $errors['value'] = 'value must be at most 255 characters';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return ['type' => $type, 'value' => $value];
    }
}
