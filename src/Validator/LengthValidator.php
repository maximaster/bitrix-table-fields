<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Validator;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\Field;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator as BitrixLengthValidator;
use Webmozart\Assert\Assert;

/**
 * Валидирует длинну строки используя mb-функции.
 */
class LengthValidator extends BitrixLengthValidator
{
    /**
     * {@inheritDoc}
     *
     * @psalm-param mixed $value
     * @psalm-param mixed $primary
     * @psalm-param mixed[] $row
     */
    public function validate($value, $primary, array $row, Field $field)
    {
        Assert::string($value);

        if ($this->min !== null && mb_strlen($value) < $this->min) {
            $mess = $this->errorPhraseMin === null
                ? Loc::getMessage($this->errorPhraseMinCode)
                : $this->errorPhraseMin;

            return $this->getErrorMessage($value, $field, $mess, ['#MIN_LENGTH#' => $this->min]);
        }

        if ($this->max !== null && mb_strlen($value) > $this->max) {
            $mess = $this->errorPhraseMax === null
                ? Loc::getMessage($this->errorPhraseMaxCode)
                : $this->errorPhraseMax;

            return $this->getErrorMessage($value, $field, $mess, ['#MAX_LENGTH#' => $this->max]);
        }

        return true;
    }
}
