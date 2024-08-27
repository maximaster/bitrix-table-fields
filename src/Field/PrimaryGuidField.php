<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\SystemException;

/**
 * GUID v4 как первичный ключ.
 */
final class PrimaryGuidField extends GuidField
{
    /**
     * @throws SystemException
     *
     * @psalm-param non-empty-string $name
     */
    public static function on(string $name): self
    {
        return new self($name, []);
    }

    protected function constructDefaults(array $parameters): void
    {
        $this->configurePrimary(true);
        $this->configureRequired(true);
        $this->configureUnique(true);
    }
}
