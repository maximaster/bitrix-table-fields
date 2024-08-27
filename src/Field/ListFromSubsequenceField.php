<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\SystemException;

/**
 * Поле, которое конвертирует последовательность в массив и наоборот.
 *
 * TODO Подумать над тем как заблокировать изменение configureSerializeCallback и configureUnserializeCallback.
 *      Либо не наследоваться от ArrayField, а тупо скопировать содержимое ArrayField с доработкой
 *      (удаление ненужного кода).
 * TODO Подумать над описанием класса.
 */
class ListFromSubsequenceField extends ArrayField
{
    /**
     * @throws SystemException
     *
     * @psalm-param array<string, mixed> $parameters
     */
    public function __construct(string $name, array $parameters = [])
    {
        parent::__construct($name, $parameters);

        $this->configureSerializeCallback([$this, 'encodeSubsequence']);
        $this->configureUnserializeCallback([$this, 'decodeSubsequence']);
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-ignore-next-line Переопределили метод (возвращаемый тип у битрикса указан в аннотациях).
     */
    public function cast($value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return self::decodeSubsequence($value);
        }

        return parent::cast($value);
    }

    /**
     * @param string[]|null $values
     *
     * @psalm-param list<string>|null $values
     */
    public static function encodeSubsequence(?array $values): ?string
    {
        return ($values === null || count($values) === 0) ? null : implode(',', $values);
    }

    /**
     * @return string[]|null
     *
     * @psalm-return list<string>|null
     */
    public static function decodeSubsequence(?string $values): ?array
    {
        return ($values === null || $values === '') ? null : explode(',', $values);
    }
}
