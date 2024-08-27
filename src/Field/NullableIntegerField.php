<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\ORM\Fields\ScalarField;
use Exception;

class NullableIntegerField extends ScalarField
{
    /**
     * @throws Exception
     */
    public function cast($value): ?int
    {
        return $this->convertValueFromDb($value);
    }

    /**
     * @throws Exception
     */
    public function convertValueFromDb($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value)) {
            $convertedValue = (string) ((int) $value);

            if ($convertedValue !== $value) {
                throw new Exception('Значение поля базы данных не является целочисленным.');
            }

            return (int) $value;
        }

        throw new Exception('Значение поля базы данных не является целочисленным.');
    }

    /**
     * По-хорошему возвращаемое значение должно быть ?int, но сделано string
     * из совместимости с Битрикс-ом.
     *
     * @throws Exception
     */
    public function convertValueToDb($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_int($value) === false) {
            throw new Exception(sprintf(
                'Значением поля типа - %s может быть либо null либо целое число.',
                self::class
            ));
        }

        return (string) $value;
    }
}
