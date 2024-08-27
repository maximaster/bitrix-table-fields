<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Field;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Entity as BitrixOrmEntity;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Filter\Condition as BitrixCondition;
use Bitrix\Main\ORM\Query\Filter\ConditionTree as BitrixConditionTree;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query as BitrixQuery;
use Bitrix\Main\SystemException;
use Maximaster\BitrixEnums\Main\Orm\JoinType;

class ReferenceField extends Reference
{
    /**
     * @param BitrixCondition|BitrixConditionTree|null $condition
     *
     * @throws ArgumentException
     * @throws SystemException
     *
     * @psalm-param non-empty-string $name
     * @psalm-param class-string<DataManager> $refTable
     * @psalm-param non-empty-string $thisField
     * @psalm-param non-empty-string $refField
     */
    public static function for(
        string $name,
        string $refTable,
        string $thisField,
        string $refField,
        JoinType $joinType,
        $condition = null
    ): self {
        $join = Join::on('this.' . $thisField, 'ref.' . $refField);

        if ($condition !== null) {
            $join->addCondition($condition);
        }

        return (new self($name, $refTable, $join))
            ->configureJoinType($joinType->getValue());
    }

    /**
     * @param BitrixCondition|BitrixConditionTree|null $condition
     *
     * @throws ArgumentException
     * @throws SystemException
     *
     * @psalm-param non-empty-string $name
     * @psalm-param non-empty-string $thisField
     * @psalm-param non-empty-string $refField
     */
    public static function forEntity(
        string $name,
        BitrixOrmEntity $refEntity,
        string $thisField,
        string $refField,
        JoinType $joinType,
        $condition = null
    ): self {
        $join = Join::on('this.' . $thisField, 'ref.' . $refField);

        if ($condition !== null) {
            $join->addCondition($condition);
        }

        return (new self($name, $refEntity, $join))
            ->configureJoinType($joinType->getValue());
    }

    /**
     * @param BitrixCondition|BitrixConditionTree|null $condition
     *
     * @throws ArgumentException
     * @throws SystemException
     *
     * @psalm-param non-empty-string $name
     * @psalm-param non-empty-string $thisField
     * @psalm-param non-empty-string $refField
     */
    public static function forQuery(
        string $name,
        BitrixQuery $refQuery,
        string $thisField,
        string $refField,
        JoinType $joinType,
        $condition = null
    ): self {
        return self::forEntity(
            $name,
            BitrixOrmEntity::getInstanceByQuery($refQuery),
            $thisField,
            $refField,
            $joinType,
            $condition
        );
    }
}
