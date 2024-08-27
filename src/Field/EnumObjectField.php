<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\SystemException;
use InvalidArgumentException;
use Maximaster\BitrixTableFields\Validator\CallableValidator;
use Maximaster\BitrixTableFields\Validator\NullableValidator;
use MyCLabs\Enum\Enum;
use ReflectionException as PhpReflectionException;
use Webmozart\Assert\Assert;

/**
 * Поле хранения {@link Enum}.
 */
class EnumObjectField extends StringField
{
    /**
     * Параметр, который принимает Callable(string|null):string|null и должен
     * вернуть новое значение которое уже будет обработано в decode.
     */
    public const PARAM_VALUE_RESTORER = 'value_restorer';
    public const PARAM_VALUE_PERSISTER = 'value_persister';
    public const PARAM_ENUM = 'enum';
    public const PARAM_NULLABLE = 'nullable';

    /**
     * Класс перечисления этого поля.
     *
     * @psalm-var class-string<Enum>
     */
    private string $enum;

    /**
     * @throws SystemException
     * @throws PhpReflectionException
     *
     * @template T of Enum
     *
     * @psalm-param non-empty-string $name
     * @psalm-param class-string<T> $enum
     * @psalm-param array<non-empty-string, mixed> $parameters
     */
    public static function required(string $name, string $enum, array $parameters = []): self
    {
        return (
            new self(
                $name,
                $parameters + [self::PARAM_ENUM => $enum, self::PARAM_NULLABLE => false]
            )
        )->configureRequired(true);
    }

    /**
     * @throws SystemException
     * @throws PhpReflectionException
     *
     * @template T of Enum
     *
     * @psalm-param non-empty-string $name
     * @psalm-param class-string<T> $enum
     * @psalm-param array<non-empty-string, mixed> $parameters
     */
    public static function on(string $name, string $enum, array $parameters = []): self
    {
        return new self($name, $parameters + [self::PARAM_ENUM => $enum, self::PARAM_NULLABLE => false]);
    }

    /**
     * @throws SystemException
     * @throws PhpReflectionException
     *
     * @template T of Enum
     *
     * @psalm-param non-empty-string $name
     * @psalm-param class-string<T> $enum
     * @psalm-param array<non-empty-string, mixed> $parameters
     */
    public static function nullable(string $name, string $enum, array $parameters = []): self
    {
        return new self($name, $parameters + [self::PARAM_ENUM => $enum, self::PARAM_NULLABLE => true]);
    }

    /**
     * Примечание: конструктор открыт для совместимости с ExpressionField,
     * при указании данного типа поля через configureValueType.
     *
     * @throws SystemException
     * @throws PhpReflectionException
     *
     * @psalm-param non-empty-string $name
     * @psalm-param array<non-empty-string, mixed> $parameters
     */
    public function __construct(string $name, array $parameters = [])
    {
        Assert::stringNotEmpty($name);
        Assert::allStringNotEmpty(array_keys($parameters));
        Assert::keyExists($parameters, self::PARAM_ENUM);

        parent::__construct($name, $parameters);

        $this->enum = $parameters[self::PARAM_ENUM];

        /** @psalm-var string[] $enumValues */
        $enumValues = $this->enum::toArray();
        $min = count($enumValues) > 0 ? mb_strlen(reset($enumValues)) : 1;
        $max = $min;

        foreach ($enumValues as $value) {
            $length = mb_strlen($value);

            if ($min > $length) {
                $min = $length;
            }

            if ($max < $length) {
                $max = $length;
            }
        }

        $this->addValidator(new LengthValidator($min, $max));
        $this->addSaveDataModifier([$this, 'encode']);
        $this->addFetchDataModifier([$this, 'decode']);
    }

    public function cast($value): ?Enum
    {
        if ($value instanceof $this->enum) {
            return $value;
        }

        if (is_object($value) || is_resource($value)) {
            throw new InvalidArgumentException(
                sprintf('Передан неподдерживаемый тип параметра: "%s".', get_debug_type($value))
            );
        }

        /** @psalm-var scalar|null $value */
        return $this->restoreValue($value === null ? null : (string) $value);
    }

    public function assureValueObject($value): Enum
    {
        return $this->cast($value);
    }

    public function encode(?Enum $enum): ?string
    {
        return $this->persistValue($enum);
    }

    public function decode(?string $value): ?Enum
    {
        return $this->restoreValue($value);
    }

    private function restoreValue(?string $value): ?Enum
    {
        if (($value === null || $value === '') && $this->allowNulls()) {
            return null;
        }

        if (array_key_exists(self::PARAM_VALUE_RESTORER, $this->initialParameters)) {
            $value = $this->initialParameters[self::PARAM_VALUE_RESTORER]($value);
        }

        return new $this->enum($value);
    }

    /**
     * @psalm-param Enum<scalar>|null $value
     */
    private function persistValue(?Enum $value): ?string
    {
        if ($value === null && $this->allowNulls()) {
            return null;
        }

        if (array_key_exists(self::PARAM_VALUE_PERSISTER, $this->initialParameters)) {
            return $this->initialParameters[self::PARAM_VALUE_PERSISTER]($value);
        }

        return $value->getValue();
    }

    private function allowNulls(): bool
    {
        return (bool) ($this->initialParameters[self::PARAM_NULLABLE] ?? false);
    }

    /**
     * {@inheritDoc}
     *
     * @throws PhpReflectionException
     * @throws SystemException
     */
    public function addValidator($validator)
    {
        return parent::addValidator(
            NullableValidator::wrapIf($this->allowNulls(), CallableValidator::normalizeFrom($validator))
        );
    }
}
