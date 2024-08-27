<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Fields\Validators\RegExpValidator;
use Bitrix\Main\SystemException;
use Maximaster\BitrixTableFields\Validator\CallableValidator;
use Maximaster\BitrixTableFields\Validator\NullableValidator;
use Orion\Process\System\Infrastructure\Orm\Field\NullableField;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Validator\GenericValidator;
use ReflectionException as PhpReflectionException;

/**
 * UUID v4.
 */
class GuidField extends StringField implements NullableField
{
    public const PARAM_NULLABLE = 'nullable';

    private bool $useCast = true;

    /**
     * @return static
     *
     * @throws PhpReflectionException
     * @throws SystemException
     *
     * @psalm-param non-empty-string $name
     */
    public static function nullable(string $name): self
    {
        return (new static($name, [self::PARAM_NULLABLE => true]))->configureRequired(false);
    }

    /**
     * @return static
     *
     * @throws PhpReflectionException
     * @throws SystemException
     *
     * @psalm-param non-empty-string $name
     */
    public static function required(string $name): self
    {
        return (new static($name, [self::PARAM_NULLABLE => false]))->configureRequired(true);
    }

    /**
     * @return static
     *
     * @throws PhpReflectionException
     * @throws SystemException
     *
     * @psalm-param non-empty-string $name
     */
    public static function requiredUnique(string $name): self
    {
        return self::required($name)->configureUnique(true);
    }

    /**
     * Создать экземпляр UUIDv4 (GUID) поля.
     *
     * @throws SystemException
     * @throws PhpReflectionException
     *
     * @psalm-param non-empty-string $name
     * @psalm-param array<non-empty-string, mixed> $parameters
     */
    final public function __construct(string $name, $parameters = [])
    {
        $preparedParameters = $this->prepareParameters($parameters);

        parent::__construct($name, $preparedParameters);

        $this->constructDefaults($preparedParameters);
        $this->constructValidation($preparedParameters);
    }

    /**
     * Для переопределения логики конструктора в наследниках.
     *
     * @psalm-param array<non-empty-string, mixed> $parameters
     * @psalm-return array<non-empty-string, mixed>
     */
    protected function prepareParameters(array $parameters): array
    {
        return $parameters;
    }

    /**
     * Для переопределения в наследниках.
     *
     * @psalm-param array<non-empty-string, mixed> $parameters
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) why:correct
     */
    protected function constructDefaults(array $parameters): void
    {
    }

    /**
     * @throws SystemException
     * @throws ArgumentTypeException
     * @throws PhpReflectionException
     *
     * @psalm-param array<non-empty-string, mixed> $parameters
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) why:correct
     */
    protected function constructValidation(array $parameters): void
    {
        $this->configureSize(36);

        $validators = [
            new RegExpValidator('/' . (new GenericValidator())->getPattern() . '/Dms'),
            // Нужен для авто-генерации SQL-запросов на создание таблицы.
            // TODO не работает, пока не сделан NullableLengthValidator extends
            //      LengthValidator, т.к. в Битрикс зашита проверка на этот
            //      класс (см. MysqlCommonSqlHelper::getColumnTypeByField).
            new LengthValidator($this->size, $this->size),
        ];

        foreach ($validators as $validator) {
            $this->addValidator($validator);
        }
    }

    public function allowNulls(): bool
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

    public function cast($value)
    {
        if ($this->useCast === false || $value === null) {
            return $value;
        }

        return parent::cast($value);
    }

    /**
     * @psalm-return non-empty-string|null
     */
    public function encode(?UuidInterface $guid): ?string
    {
        if ($guid === null && $this->allowNulls()) {
            return null;
        }

        return $guid->toString();
    }

    /**
     * @psalm-param non-empty-string|null $guid
     */
    public function decode(?string $guid): ?UuidInterface
    {
        if ($guid === null && $this->allowNulls()) {
            return null;
        }

        return Uuid::fromString($guid);
    }

    public function isValueEmpty($value): bool
    {
        return in_array($value, ['', null], true);
    }

    /**
     * @psalm-return non-empty-string
     */
    public function getDataType(): string
    {
        return 'string';
    }
}
