<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\SystemException;

/**
 * Поле, возвращающее статичное значение.
 */
class StaticValueField extends ExpressionField
{
    private function __construct(string $name, $value)
    {
        if (is_bool($value)) {
            $this->configureValueType(BooleanField::class);
        }

        parent::__construct($name, var_export($value, true));
    }

    /**
     * @throws SystemException
     */
    public static function on(string $name, $value): self
    {
        return new self($name, $value);
    }
}
