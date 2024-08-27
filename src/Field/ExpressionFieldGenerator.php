<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use DateTimeInterface;

/**
 * Вспомогательный класс для генерации вычисляемых полей.
 *
 * @psalm-immutable
 */
class ExpressionFieldGenerator
{
    public array $values = [];
    public array $fields = [];

    public function field(string $field): string
    {
        $this->fields[] = $field;

        return '%%s';
    }

    public function value($value): string
    {
        switch (true) {
            case $value instanceof DateTimeInterface:
                $this->values[] = $value->format('Y-m-d H:i:s');
                break;
            default:
                $this->values[] = var_export($value, true);
        }

        return '%s';
    }

    public function raw(string $value): string
    {
        return $value;
    }
}
