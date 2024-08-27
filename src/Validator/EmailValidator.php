<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Validator;

use Bitrix\Main\ORM\Fields\Field;
use Bitrix\Main\ORM\Fields\FieldError;
use Bitrix\Main\ORM\Fields\Validators\IValidator;

/**
 * TODO Рассмотрите возможность локализации сообщений об ошибках.
 */
class EmailValidator implements IValidator
{
    /** @psalm-var non-empty-string */
    private string $errorMessage;

    public static function default(): self
    {
        return new self(null);
    }

    /**
     * @psalm-param non-empty-string $errorMessage
     */
    public static function on(string $errorMessage): self
    {
        return new self($errorMessage);
    }

    /**
     * @psalm-param non-empty-string|null $errorMessage
     */
    final private function __construct(?string $errorMessage = null)
    {
        $this->errorMessage = $errorMessage === null
            ? 'Value is not a valid email address.'
            : $errorMessage;
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-ignore-next-line why:dependency:mistyping
     */
    final public function validate($value, $primary, array $row, Field $field)
    {
        return is_string(filter_var($value, FILTER_VALIDATE_EMAIL)) && $this->isValidEmail($value) === true
            ? true
            : new FieldError($field, $this->errorMessage, FieldError::INVALID_VALUE);
    }

    /**
     * @psalm-param non-empty-string $email
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) why:correct У других классов могут быть проверки.
     */
    protected function isValidEmail(string $email): bool
    {
        return true;
    }
}
