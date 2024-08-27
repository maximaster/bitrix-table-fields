<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\SystemException;

/**
 * Преобразует значение другого столбца в другой тип значения.
 */
class CastField extends ExpressionField
{
    /**
     * @throws SystemException
     */
    public static function boolFromNullable(string $name, string $source): self
    {
        return (new self($name, '%s IS NOT NULL', [$source]))->configureValueType(BooleanField::class);
    }
}
