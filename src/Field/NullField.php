<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\ORM\Fields\ScalarField;
use Exception as PhpException;

class NullField extends ScalarField
{
    /**
     * {@inheritDoc}
     *
     * @return null
     *
     * @throws PhpException
     *
     * @psalm-assert string|null $value
     */
    public function cast($value)
    {
        $this->assertDataType($value);

        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @return null
     */
    public function convertValueFromDb($value)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @return null
     *
     * @phpstan-ignore-next-line why:dependency
     */
    public function convertValueToDb($value)
    {
        return null;
    }

    /**
     * @throws PhpException
     *
     * @psalm-assert string|null $value
     */
    public function isValueEmpty($value): bool
    {
        $this->assertDataType($value);

        return in_array($value, ['', null], true);
    }

    /**
     * @psalm-return non-empty-string
     */
    public function getDataType(): string
    {
        return 'string';
    }

    /**
     * @throws PhpException
     */
    private function assertDataType($value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        throw new PhpException('Передан некорректный формат данных');
    }
}
