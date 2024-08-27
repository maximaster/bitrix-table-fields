<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Maximaster\BitrixEnums\Main\Orm\OrderDirection;
use ReflectionException;

/**
 * Выбирает список связанных значений из другой таблицы.
 */
final class ExternalListField extends ExternalField
{
    private const SELECT_FIELD = 'LIST';

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

        $this->configureValueType(ListFromSubsequenceField::class);
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
            ->registerRuntimeField(
                null,
                self::buildConcatenatedIds($targetTableField, $order)
            )
            ->setSelect([self::SELECT_FIELD]);
    }

    /**
     * @throws SystemException
     *
     * @psalm-param non-empty-string $targetTableField
     * @psalm-param array<non-empty-string, 'asc'|'desc'> $order
     */
    private static function buildConcatenatedIds(string $targetTableField, array $order): ExpressionField
    {
        switch (count($order)) {
            case 0:
                $orderExpression = '';
                break;
            default:
                $orderExpression = [];
                foreach ($order as $direction) {
                    $orderExpression[] = '%s ' . OrderDirection::from($direction)->getValue();
                }

                $orderExpression = ' ORDER BY ' . implode(', ', $orderExpression);
        }

        return new ExpressionField(
            self::SELECT_FIELD,
            "GROUP_CONCAT(%s$orderExpression)",
            [$targetTableField, ...array_keys($order)]
        );
    }
}
