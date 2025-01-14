<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\ORM\Fields\Field;
use Bitrix\Main\ORM\Fields\FieldError;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\SystemException;
use Maximaster\BitrixTableFields\Validator\CallableValidator;
use Maximaster\BitrixTableFields\Validator\NullableValidator;
use Nepada\EmailAddress\EmailAddress;
use Nepada\EmailAddress\RfcEmailAddress;
use ReflectionException as PhpReflectionException;
use Webmozart\Assert\Assert;

/**
 * Поле, которое хранит данные для {@link EmailAddress}.
 */
class EmailField extends TextField
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
    final private function __construct(string $name, array $parameters = [])
    {
        Assert::stringNotEmpty($name);
        Assert::allStringNotEmpty(array_keys($parameters));

        parent::__construct($name, $parameters);

        $this->addValidator(
            static fn ($value, $primary, array $row, Field $field) => $value instanceof EmailAddress
                ? true
                : new FieldError($field, 'Value is not a valid email address.', FieldError::INVALID_VALUE)
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
    }

    /**
     * @param EmailAddress|string|null $value
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
     * @psalm-return EmailAddress|null
     *
     * @phpstan-ignore-next-line why:intended
     */
    public function cast($value): ?EmailAddress
    {
        $value = $value === '' ? null : $value;

        if ($value === null && $this->allowNulls() === true) {
            return null;
        }

        return RfcEmailAddress::fromString($value);
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-assert string|null $value
     *
     * @psalm-return EmailAddress|null
     *
     * @phpstan-ignore-next-line why:intended
     */
    public function convertValueFromDb($value): ?EmailAddress
    {
        $value = $value === '' ? null : $value;

        if ($value === null && $this->allowNulls() === true) {
            return null;
        }

        return RfcEmailAddress::fromString($value);
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-param EmailAddress|null $value
     *
     * @psalm-return non-empty-string|null
     *
     * @phpstan-ignore-next-line why:dependency:mistyping
     */
    public function convertValueToDb($value): ?string
    {
        // @phpstan-ignore-next-line why:false-positive
        if ($value === null && $this->allowNulls() === true) {
            return null;
        }

        return $value->toString();
    }
}
