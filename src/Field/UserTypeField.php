<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\ORM\Fields\UserTypeField as BitrixUserTypeField;
use Bitrix\Main\SystemException;

/**
 * Расширенная версия базового {@link BitrixUserTypeField}.
 *
 * @property array<non-empty-string, mixed> $initialParameters
 */
class UserTypeField extends BitrixUserTypeField
{
    /**
     * Создать экземпляр из нативного экземпляра Битрикс.
     *
     * @throws SystemException
     */
    public static function fromBitrix(BitrixUserTypeField $field): self
    {
        $copy = new self($field->name, $field->expression, $field->buildFrom, $field->initialParameters);

        // Свойства Field.
        $copy->name = $field->name;
        $copy->dataType = $field->dataType;
        $copy->initialParameters = $field->initialParameters;
        $copy->title = $field->title;
        $copy->validation = $field->validation;
        $copy->validators = $field->validators;
        $copy->additionalValidators = $field->additionalValidators;
        $copy->fetchDataModification = $field->fetchDataModification;
        $copy->fetchDataModifiers = $field->fetchDataModifiers;
        $copy->additionalFetchDataModifiers = $field->additionalFetchDataModifiers;
        $copy->saveDataModification = $field->saveDataModification;
        $copy->saveDataModifiers = $field->saveDataModifiers;
        $copy->additionalSaveDataModifiers = $field->additionalSaveDataModifiers;
        $copy->isSerialized = $field->isSerialized;
        $copy->parentField = $field->parentField;
        $copy->entity = $field->entity;

        // Свойства ExpressionField.
        $copy->expression = $field->expression;
        $copy->fullExpression = $field->fullExpression;
        $copy->valueType = $field->valueType;
        $copy->valueField = $field->valueField;
        $copy->buildFrom = $field->buildFrom;
        $copy->buildFromChains = $field->buildFromChains;
        $copy->isAggregated = $field->isAggregated;
        $copy->hasSubquery = $field->hasSubquery;

        // Свойства UserTypeField.
        $copy->isMultiple = $field->isMultiple;

        return $copy;
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

    public function setValueField(ScalarField $field): void
    {
        $this->valueField = $field;
        $this->valueType = get_class($this->valueField);
    }
}
