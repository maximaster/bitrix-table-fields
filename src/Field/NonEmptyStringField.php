<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\ORM\Fields\Field;
use Bitrix\Main\ORM\Fields\FieldError;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\SystemException;
use Maximaster\BitrixTableFields\Validator\CallableValidator;
use Maximaster\BitrixTableFields\Validator\NullableValidator;
use ReflectionException as PhpReflectionException;
use Webmozart\Assert\Assert;

class NonEmptyStringField extends ScalarField
{
    public const PARAM_NULLABLE = 'nullable';

    /**
     * @throws SystemException
     * @throws PhpReflectionException
     *
     * @psalm-param non-empty-string $name
     */
    public static function required(string $name): self
    {
        return static::on($name, [self::PARAM_NULLABLE => false])->configureRequired(true);
    }

    /**
     * @throws SystemException
     * @throws PhpReflectionException
     *
     * @psalm-param non-empty-string $name
     */
    public static function nullable(string $name): self
    {
        return static::on($name, [self::PARAM_NULLABLE => true])->configureRequired(false);
    }

    /**
     * @throws SystemException
     * @throws PhpReflectionException
     *
     * @psalm-param non-empty-string $name
     * @psalm-param array<non-empty-string, mixed> $parameters
     */
    public static function on(string $name, array $parameters = []): self
    {
        return new static($name, $parameters);
    }

    /**
     * {@inheritDoc}
     *
     * @throws SystemException
     * @throws PhpReflectionException
     *
     * @psalm-param non-empty-string $name
     * @psalm-param array<non-empty-string, mixed> $parameters
     */
    final public function __construct(string $name, $parameters = [])
    {
        Assert::stringNotEmpty($name);
        Assert::allStringNotEmpty(array_keys($parameters));

        parent::__construct($name, $parameters);

        $this->addValidator(
            static fn ($value, $primary, array $row, Field $field) => is_string($value) && $value !== ''
                ? true
                : new FieldError($field, 'The value is not a non-empty string.', FieldError::INVALID_VALUE)
        );
    }

    private function allowNulls(): bool
    {
        return isset($this->initialParameters[self::PARAM_NULLABLE])
            && $this->initialParameters[self::PARAM_NULLABLE];
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

    /**
     * {@inheritDoc}
     *
     * @psalm-return non-empty-string
     */
    public function getDataType(): string
    {
        return 'string';
        // TODO Из-за битрикса. Проблема при построении запроса.
        // return 'non_empty_string';
    }

    /**
     * @param string|null $value
     *
     * @phpstan-ignore-next-line why:intended
     */
    public function isValueEmpty($value): bool
    {
        return in_array($value, ['', null], true);
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-assert string|null $value
     *
     * @psalm-return non-empty-string|null
     */
    public function cast($value): ?string
    {
        if (($value === null || $value === '') && $this->allowNulls() === true) {
            return null;
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-assert string|null $value
     *
     * @psalm-return non-empty-string|null
     */
    public function convertValueFromDb($value): ?string
    {
        if (($value === null || $value === '') && $this->allowNulls() === true) {
            return null;
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     *
     * @throws SystemException
     *
     * @psalm-assert non-empty-string|null $value
     *
     * @psalm-return non-empty-string|null
     *
     * @phpstan-ignore-next-line why:dependency:mistyping
     */
    public function convertValueToDb($value): ?string
    {
        if (($value === null || $value === '') && $this->allowNulls() === true) {
            return null;
        }

        // Приходится делать такой костыль, иначе Битрикс некорректно строит
        // фильтр, например LIKE.
        return $this->getConnection()->getSqlHelper()->convertToDbString($value);
    }
}
