<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\ORM\Fields\ExpressionField as BitrixExpressionField;
use Bitrix\Main\SystemException;
use Maximaster\BitrixOrmCondition\Column;

/**
 * Расширенная версия базового ExpressionField.
 *
 * @property array<non-empty-string, mixed> initialParameters
 */
class ExpressionField extends BitrixExpressionField
{
    /**
     * Компилирует выражение из генерируемых подстановок.
     *
     * @throws SystemException
     */
    public static function compile(string $column, callable $worker, array $parameters = []): self
    {
        $generator = new ExpressionFieldGenerator();

        return new self(
            $column,
            sprintf($worker($generator), ...$generator->values),
            $generator->fields,
            $parameters
        );
    }

    /**
     * Создаёт выражение с анонимным названием.
     *
     * @param string[] $buildFrom
     * @param string[] $parameters
     *
     * @throws SystemException
     *
     * @psalm-param array<string, string> $parameters
     */
    public static function unnamed(string $expression, array $buildFrom = [], array $parameters = []): self
    {
        static $anonIndex = 0;

        return new self(sprintf('ANON_%d', ++$anonIndex), $expression, $buildFrom, $parameters);
    }

    /**
     * Создать экземпляр из нативного экземпляра Битрикс.
     *
     * @throws SystemException
     */
    public static function fromBitrix(BitrixExpressionField $field): self
    {
        $copy = new self($field->name, $field->expression, $field->buildFrom, $field->initialParameters);

        $copy->expression = $field->expression;
        $copy->fullExpression = $field->fullExpression;
        $copy->valueType = $field->valueType;
        $copy->valueField = $field->valueField;
        $copy->buildFrom = $field->buildFrom;
        $copy->buildFromChains = $field->buildFromChains;
        $copy->isAggregated = $field->isAggregated;
        $copy->hasSubquery = $field->hasSubquery;

        return $copy;
    }

    public function toColumn(): Column
    {
        return Column::expressedAs($this);
    }

    /**
     * @psalm-return list<non-empty-string>
     */
    public function buildFrom(): array
    {
        return $this->buildFrom;
    }

    /**
     * @psalm-return array<non-empty-string, mixed>
     */
    public function initialParameters(): array
    {
        return $this->initialParameters;
    }
}
