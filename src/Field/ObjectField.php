<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\SystemException;

use RuntimeException;

/**
 * Поле хранения объекта, который восстанавливается и сохраняется с
 * использованием пользовательских функций.
 *
 * @template T
 *
 * @psalm-immutable
 */
class ObjectField extends TextField
{
    /**
     * Класс объекта который хранится в поле.
     */
    public string $objectClass;

    /**
     * @var callable
     *
     * @psalm-param Closure(T $object):string
     */
    private $persister;

    /**
     * @var callable
     *
     * @psalm-param Closure(string $stored):T
     */
    private $restorer;

    /**
     * @throws SystemException
     *
     * @psalm-param class-string<T> $objectClass
     * @psalm-param Closure(T $object):string $persister
     * @psalm-param Closure(string $stored):T $restorer
     * @psalm-param array<non-empty-string, mixed> $parameters
     */
    public function __construct(
        string $name,
        string $objectClass,
        ?callable $persister = null,
        ?callable $restorer = null,
        array $parameters = []
    ) {
        parent::__construct($name, $parameters);

        $this->objectClass = $objectClass;
        $this->persister = $persister ?? static fn (object $object) => serialize($object);
        $this->restorer = $restorer ?? static fn (string $stored) => unserialize($stored);

        $this->addSaveDataModifier([$this, 'persist']);
        $this->addFetchDataModifier([$this, 'restore']);
    }

    /**
     * @psalm-return T|null
     * @phpstan-ignore-next-line why:dependency
     */
    public function cast($value): ?object
    {
        if ($value === null || is_object($value)) {
            return $value;
        }

        return $this->restore(strval($value));
    }

    public function persist(?object $object): ?string
    {
        if ($object === null) {
            return null;
        }

        $persistable = ($this->persister)($object);
        if ($persistable === null) {
            return null;
        }

        if (is_string($persistable) === false) {
            throw new RuntimeException(
                sprintf(
                    'Ожидалось, что persister вернёт строку, получено: %s.',
                    is_object($persistable) ? get_class($persistable) : gettype($persistable)
                )
            );
        }

        return $persistable;
    }

    public function restore(?string $stored): ?object
    {
        if ($stored === null) {
            return null;
        }

        $restored = ($this->restorer)($stored);

        if (($restored instanceof $this->objectClass) === false) {
            throw new RuntimeException(
                sprintf(
                    'Ожидалось, что restorer вернёт %s, но был получен %s.',
                    $this->objectClass,
                    is_object($restored) ? get_class($restored) : gettype($restored)
                )
            );
        }

        return $restored;
    }
}
