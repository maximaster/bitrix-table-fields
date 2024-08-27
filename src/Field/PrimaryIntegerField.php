<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\SystemException;
use Webmozart\Assert\Assert;

/**
 * TODO Extends {@link PositiveIntegerField}.
 *
 * Автоинкрементный числовой идентификатор.
 */
class PrimaryIntegerField extends IntegerField
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

    /**
     * {@inheritDoc}
     *
     * @throws SystemException
     *
     * @psalm-param non-empty-string $name
     * @psalm-param array<non-empty-string, mixed> $parameters
     */
    public function __construct(string $name, array $parameters = [])
    {
        Assert::stringNotEmpty($name);
        Assert::allStringNotEmpty(array_keys($parameters));

        parent::__construct($name, $parameters);

        $this
            ->configurePrimary(true)
            ->configureAutocomplete(true);
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-return non-empty-string
     */
    public function getDataType(): string
    {
        return 'integer';
    }
}
