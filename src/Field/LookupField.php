<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Field;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\SystemException;
use InvalidArgumentException;
use ReflectionException;
use ReflectionProperty;
use Webmozart\Assert\Assert;

/**
 * Поле из другой таблицы.
 */
class LookupField extends ExpressionField
{
    /**
     * @throws SystemException
     */
    public static function for(string $name, string $ref, string $field): self
    {
        return new self($name, '%s', [sprintf('%s.%s', $ref, $field)]);
    }

    /**
     * @param string[] $target
     *
     * @throws ReflectionException
     * @throws SystemException
     *
     * @psalm-param array<non-empty-string, mixed> $valueTypeParameters
     *
     * @psalm-assert non-empty-string $name
     * @psalm-assert non-empty-string $ref
     * @psalm-assert array{class-string<DataManager>, non-empty-string} $target
     */
    public static function configured(string $name, string $ref, array $target, array $valueTypeParameters = []): self
    {
        Assert::isList($target);
        Assert::count($target, 2);
        Assert::allStringNotEmpty(array_keys($valueTypeParameters));

        /** @var DataManager $targetTableClass */
        [$targetTableClass, $targetFieldName] = $target;
        $targetFieldMap = $targetTableClass::getMap();

        if (array_key_exists($targetFieldName, $targetFieldMap) === false) {
            throw new InvalidArgumentException('Переданы некорректные параметры.');
        }

        /** @var Field $targetField */
        $targetField = $targetFieldMap[$targetFieldName];
        $parameters = ['data_type_parameters' => $valueTypeParameters];

        if ($targetField instanceof ScalarField) {
            $parameters['data_type_parameters']['default_value'] = $targetField->getDefaultValue();
        }

        $reflectionProperty = new ReflectionProperty($targetField, 'initialParameters');

        $reflectionProperty->setAccessible(true);

        $parameters['data_type_parameters'] = array_merge_recursive(
            $parameters['data_type_parameters'],
            (array) $reflectionProperty->getValue($targetField)
        );

        return (new self($name, '%s', [sprintf('%s.%s', $ref, $targetFieldName)], $parameters))
            ->configureValueType(
                is_a($targetField, PrimaryGuidField::class)
                    ? GuidField::class
                    : get_class($targetField)
            );
    }
}
