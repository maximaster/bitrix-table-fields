<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\ObjectException;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date as BitrixDate;
use Bitrix\Main\Type\DateTime as BitrixDateTime;
use DateTimeImmutable as PhpDateTimeImmutable;
use Exception as PhpException;
use Maximaster\BitrixTableFields\Validator\CallableValidator;
use Maximaster\BitrixTableFields\Validator\NullableValidator;
use ReflectionException as PhpReflectionException;
use Webmozart\Assert\Assert;

/**
 * Поле хранения не изменяемой метки времени.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DateTimeImmutableField extends DatetimeField
{
    public const PARAM_NULLABLE = 'nullable';

    /**
     * @throws SystemException
     *
     * @psalm-param non-empty-string $name
     */
    public static function required(string $name): self
    {
        return (self::on($name, [self::PARAM_NULLABLE => false]))->configureRequired(true);
    }

    /**
     * @throws SystemException
     *
     * @psalm-param non-empty-string $name
     */
    public static function nullable(string $name): self
    {
        return self::on($name, [self::PARAM_NULLABLE => true])->configureRequired(false);
    }

    /**
     * Создать экземпляр для столбца.
     *
     * @throws SystemException
     *
     * @psalm-param non-empty-string $name
     * @psalm-param array<non-empty-string, mixed> $parameters
     */
    public static function on(string $name, array $parameters = []): self
    {
        return new self($name, $parameters);
    }

    /**
     * {@inheritDoc}
     *
     * @throws SystemException
     *
     * @psalm-param non-empty-string $name
     * @psalm-param array<non-empty-string, mixed> $parameters
     */
    public function __construct(string $name, $parameters = [])
    {
        Assert::stringNotEmpty($name);
        Assert::allStringNotEmpty(array_keys($parameters));

        if (array_key_exists(self::PARAM_NULLABLE, $parameters) === false) {
            $parameters[self::PARAM_NULLABLE] = true;
        }

        parent::__construct($name, $parameters + [
            // Выключаем стандартный валидатор, т.к. он будет пытаться преобразовать DateTimeImmutable в строку и упадёт
            'validation' => static fn (): array => [],
        ]);

        $this->addSaveDataModifier([$this, 'encode']);
        $this->addFetchDataModifier([$this, 'decode']);
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
     * @param string|PhpDateTimeImmutable|null $value
     *
     * @throws ObjectException
     * @throws SystemException
     *
     * @psalm-return non-empty-string|PhpDateTimeImmutable|null
     *
     * @phpstan-ignore-next-line why:dependency:mistyping
     */
    public function cast($value): ?PhpDateTimeImmutable
    {
        if ($value === null && $this->allowNulls() === true) {
            return null;
        }

        if ($value instanceof PhpDateTimeImmutable) {
            return $value;
        }

        return $this->decode(parent::cast($value));
    }

    /**
     * {@inheritDoc}
     *
     * @param string|PhpDateTimeImmutable|null $value
     *
     * @throws ObjectException
     * @throws SystemException
     *
     * @psalm-return non-empty-string|PhpDateTimeImmutable|null
     *
     * @phpstan-ignore-next-line why:dependency:mistyping
     */
    public function assureValueObject($value): ?PhpDateTimeImmutable
    {
        if ($value === null && $this->allowNulls() === true) {
            return null;
        }

        if ($value instanceof PhpDateTimeImmutable) {
            return $value;
        }

        return $this->decode(parent::assureValueObject($value));
    }

    public function encode(?PhpDateTimeImmutable $dateTime): ?BitrixDateTime
    {
        if ($dateTime === null && $this->allowNulls() === true) {
            return null;
        }

        return BitrixDateTime::createFromTimestamp($dateTime->getTimestamp());
    }

    /**
     * @throws ObjectException
     * @throws PhpException
     */
    public function decode($dateTime): ?PhpDateTimeImmutable
    {
        if ($dateTime === null && $this->allowNulls() === true) {
            return null;
        }

        if (is_string($dateTime)) {
            return $this->decode(new BitrixDateTime($dateTime, 'Y-m-d H:i:s'));
        }

        if (($dateTime instanceof BitrixDate) === false) {
            throw new PhpException(
                sprintf('Неожиданный тип данных для метки времени: %s.', get_debug_type($dateTime))
            );
        }

        return (new PhpDateTimeImmutable())->setTimestamp($dateTime->getTimestamp());
    }

    public function isValueEmpty($value): bool
    {
        return in_array($value, ['', null], true);
    }

    /**
     * {@inheritDoc}
     *
     * @param string|PhpDateTimeImmutable|null $value
     *
     * @throws ObjectException
     * @throws SystemException
     *
     * @psalm-param non-empty-string|PhpDateTimeImmutable|null $value
     *
     * @phpstan-ignore-next-line why:dependency:mistyping
     */
    public function convertValueFromDb($value): ?PhpDateTimeImmutable
    {
        if ($value === null && $this->allowNulls() === true) {
            return null;
        }

        if ($value instanceof PhpDateTimeImmutable) {
            return $value;
        }

        return $this->decode(parent::convertValueFromDb($value));
    }

    /**
     * {@inheritDoc}
     *
     * @param string|PhpDateTimeImmutable|null $value
     *
     * @psalm-param non-empty-string|PhpDateTimeImmutable|null $value
     *
     * @psalm-return non-empty-string|null
     *
     * @phpstan-ignore-next-line why:dependency:mistyping
     */
    public function convertValueToDb($value): ?string
    {
        if ($value === null && $this->allowNulls() === true) {
            return null;
        }

        if ($value instanceof PhpDateTimeImmutable) {
            $value = $this->encode($value);
        }

        if (is_string($value) && $value !== '') {
            $value = new BitrixDateTime($value);
        }

        $converted = parent::convertValueToDb($value);
        if ($converted === null) {
            return null;
        }

        return $converted;
    }

    /**
     * {@inheritDoc}
     */
    public function getDataType(): string
    {
        return 'datetime';
    }
}
