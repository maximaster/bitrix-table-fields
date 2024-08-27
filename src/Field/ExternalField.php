<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\String\LazyString;

/**
 * Выбирает связанное значение из другой таблицы.
 *
 * @phpstan-consistent-constructor
 * @psalm-immutable
 */
class ExternalField extends ExpressionField
{
    /** @psalm-var class-string<DataManager> */
    public string $targetTable;
    /** @psalm-var non-empty-string */
    public string $targetTableField;

    /**
     * @param string[] $buildFrom
     *
     * @throws SystemException
     */
    protected function __construct(
        string $name,
        $expression,
        array $buildFrom = []
    ) {
        parent::__construct($name, $expression, $buildFrom);
    }

    /**
     * @param DataManager|string $targetTable
     * @param string[] $filter
     *
     * @throws ArgumentException
     * @throws SystemException
     *
     * @psalm-param non-empty-string $name
     * @psalm-param class-string<DataManager> $targetTable
     * @psalm-param non-empty-string $targetTableField
     * @psalm-param array<non-empty-string, non-empty-string> $filter
     * @psalm-param array<non-empty-string, 'asc'|'desc'> $order
     * @psalm-param null|Closure(Query):void $queryConfigurer
     */
    public static function from(
        string $name,
        string $targetTable,
        string $targetTableField,
        array $filter,
        array $order = [],
        ?callable $queryConfigurer = null
    ): self {
        $field = new static(
            $name,
            self::buildLazyQuery($name, $targetTable, $targetTableField, $filter, $order, $queryConfigurer),
            array_values($filter)
        );

        $field->targetTable = $targetTable;
        $field->targetTableField = $targetTableField;

        return $field;
    }

    /**
     * @param DataManager|string $targetTable
     * @param string[] $filter
     *
     * @psalm-param non-empty-string $name
     * @psalm-param class-string<DataManager> $targetTable
     * @psalm-param non-empty-string $targetTableField
     * @psalm-param array<non-empty-string, non-empty-string> $filter
     * @psalm-param array<non-empty-string, 'asc'|'desc'> $order
     * @psalm-param null|Closure(Query):void $queryConfigurer
     */
    private static function buildLazyQuery(
        string $name,
        string $targetTable,
        string $targetTableField,
        array $filter,
        array $order,
        ?callable $queryConfigurer
    ): LazyString {
        return LazyString::fromCallable(static function () use (
            $name,
            $filter,
            $targetTableField,
            $targetTable,
            $order,
            $queryConfigurer
        ) {
            return sprintf(
                '(%s)',
                static::buildQuery(
                    $name,
                    $targetTable,
                    $targetTableField,
                    $filter,
                    $order,
                    $queryConfigurer
                )->getQuery()
            );
        });
    }

    /**
     * @param DataManager|string $targetTable
     *
     * @psalm-param non-empty-string $name
     * @psalm-param class-string<DataManager> $targetTable
     * @psalm-param non-empty-string $targetTableField
     * @psalm-param array<non-empty-string, non-empty-string> $filter
     * @psalm-param array<non-empty-string, 'asc'|'desc'> $order
     * @psalm-param null|Closure(Query):void $queryConfigurer
     *
     * @throws ArgumentException
     * @throws ReflectionException
     * @throws SystemException
     */
    protected static function buildQuery(
        string $name,
        string $targetTable,
        string $targetTableField,
        array $filter,
        array $order,
        ?callable $queryConfigurer
    ): Query {
        return self::buildBaseQuery($name, $targetTable, $targetTableField, $filter, $queryConfigurer)
            ->setSelect([$targetTableField])
            ->setOrder($order)
            ->setLimit(1);
    }

    /**
     * Строит базовый запрос.
     *
     * @param DataManager|string $targetTable
     *
     * @psalm-param non-empty-string $name
     * @psalm-param class-string<DataManager> $targetTable
     * @psalm-param non-empty-string $targetTableField
     * @psalm-param array<non-empty-string, non-empty-string> $filter
     * @psalm-param null|Closure(Query):void $queryConfigurer
     *
     * @throws ArgumentException
     * @throws SystemException
     * @throws ReflectionException
     */
    protected static function buildBaseQuery(
        string $name,
        string $targetTable,
        string $targetTableField,
        array $filter,
        ?callable $queryConfigurer
    ): Query {
        $query = (new Query($targetTable::getEntity()))
            // Ставим уникальное имя, иначе alias из подзапроса может
            // совпасть с существующим выше alias'ом и перекрыть его.
            ->setCustomBaseTableAlias(
                sprintf(
                    '%s_%s_%s',
                    strtolower((new ReflectionClass($targetTable))->getShortName()),
                    $name,
                    $targetTableField
                )
            );

        foreach (array_keys($filter) as $conditionColumn) {
            $query->addFilter($conditionColumn, new SqlExpression('%s'));
        }

        if ($queryConfigurer !== null) {
            $queryConfigurer($query);
        }

        return $query;
    }
}
