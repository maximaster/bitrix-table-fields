<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Validator;

use Bitrix\Main\ORM\EntityError;
use Bitrix\Main\ORM\Fields\Field;
use Bitrix\Main\ORM\Fields\Validators\Validator;

/**
 * Разрешает только те значения, которые будут сохраняться в новый объект.
 */
class NonUpdatableFieldValidator extends Validator
{
    /**
     * {@inheritDoc}
     */
    public function validate($value, $primary, array $row, Field $field)
    {
        // Примечание: Битрикс использует `null` для `$primary`, если запись новая (отсутствует в базе данных).
        return $primary === null
            ? new EntityError(sprintf('Запрещено обновлять поле "%s".', $field->getName()))
            : true;
    }
}
