<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\SystemException;
use Maximaster\BitrixTableFields\Validator\CallableValidator;
use Maximaster\BitrixTableFields\Validator\NullableValidator;
use ReflectionException as PhpReflectionException;

/**
 * Поле хранящее данные в JSON.
 */
class JsonField extends TextField
{
    public const PARAM_NULLABLE = 'nullable';

    public function __construct($name, $parameters = [])
    {
        $this->addSaveDataModifier([$this, 'encode']);
        $this->addFetchDataModifier([$this, 'decode']);

        parent::__construct($name, $parameters);
    }

    public function encode($value): ?string
    {
        if ($value === null && $this->allowNulls()) {
            return null;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param string|null $value
     *
     * @return array|null
     */
    public function decode($value)
    {
        if (($value === null || $value === '') && $this->allowNulls()) {
            return null;
        }

        // @phpstan-ignore-next-line why:false-positive
        return is_string($value) === false ? (array) $value : json_decode($value, true);
    }

    /**
     * @phpstan-ignore-next-line why:intended
     */
    public function cast($value): ?array
    {
        if (($value === null || $value === '') && $this->allowNulls()) {
            return null;
        }

        return is_string($value) ? $this->decode($value) : (array) $value;
    }

    public function convertValueFromDb($value): string
    {
        return $this->getConnection()->getSqlHelper()->convertFromDbString($value);
    }

    public function convertValueToDb($value): string
    {
        return $this->getConnection()->getSqlHelper()->convertToDbString($value);
    }

    public function getDataType()
    {
        return 'text';
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
