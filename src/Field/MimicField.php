<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\SystemException;

/**
 * Поле дублирующее значение другого поля.
 */
class MimicField extends ExpressionField
{
    /**
     * @throws SystemException
     */
    public static function of(string $fieldName, string $sourceField): self
    {
        return new self($fieldName, $sourceField);
    }

    public function __construct(string $fieldName, string $sourceField)
    {
        parent::__construct($fieldName, '%s', [$sourceField]);
    }
}
