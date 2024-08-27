<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Validator;

use Bitrix\Main\ORM\Fields\Field;
use Bitrix\Main\ORM\Fields\Validators\IValidator;

/**
 * Разрешает null значения, остальные проверяет по-указанному валидатору.
 */
class NullableValidator implements IValidator
{
    private IValidator $realValidator;

    public static function wrapIf(bool $shouldWrap, IValidator $realValidator): IValidator
    {
        return $shouldWrap ? new self($realValidator) : $realValidator;
    }

    public function __construct(IValidator $realValidator)
    {
        $this->realValidator = $realValidator;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($value, $primary, array $row, Field $field)
    {
        return $value === null ? true : $this->realValidator->validate($value, $primary, $row, $field);
    }
}
