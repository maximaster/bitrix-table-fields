<?php

declare(strict_types=1);

namespace Maximaster\BitrixTableFields\Validator;

use Bitrix\Main\ORM\EntityError;
use Bitrix\Main\ORM\Fields\Field;
use Bitrix\Main\ORM\Fields\Validators\IValidator;
use Closure;
use InvalidArgumentException as PhpInvalidArgumentException;
use ReflectionException as PhpReflectionException;
use ReflectionFunction as PhpReflectionFunction;
use ReflectionNamedType as PhpReflectionNamedType;

/**
 * Разрешает null значения, остальные проверяет по-указанному валидатору.
 *
 * @psalm-type CallableValidatorType = callable(mixed, mixed, array<mixed>, Field):(string|bool|EntityError)
 * @psalm-type ClosureValidatorType = Closure(mixed, mixed, array<mixed>, Field):(string|bool|EntityError)
 */
class CallableValidator implements IValidator
{
    private const NUMBER_OF_PARAMETERS = 4;

    /** @psalm-var ClosureValidatorType */
    private Closure $validateCallback;

    /**
     * @param IValidator|callable $validator
     *
     * @throws PhpInvalidArgumentException
     * @throws PhpReflectionException
     *
     * @psalm-param IValidator|CallableValidatorType $validator
     */
    public static function normalizeFrom($validator): self
    {
        if ($validator instanceof self) {
            return $validator;
        }

        if ($validator instanceof IValidator) {
            $validator = [$validator, 'validate'];
        }

        return new self($validator);
    }

    /**
     * @throws PhpInvalidArgumentException
     * @throws PhpReflectionException
     *
     * @psalm-param CallableValidatorType $validateCallback
     */
    public function __construct(callable $validateCallback)
    {
        $validateCallback = Closure::fromCallable($validateCallback);
        $reflection = new PhpReflectionFunction($validateCallback);

        if ($reflection->getNumberOfParameters() !== self::NUMBER_OF_PARAMETERS) {
            throw new PhpInvalidArgumentException('Количество параметров не соответствует ожидаемому.');
        }

        /** @var PhpReflectionNamedType $thirdParameterType */
        $thirdParameterType = $reflection->getParameters()[2]->getType();

        if ($thirdParameterType->isBuiltin() === false || $thirdParameterType->getName() !== 'array') {
            throw new PhpInvalidArgumentException('Тип третьего параметра не соответствует ожидаемому.');
        }

        /** @var PhpReflectionNamedType $fourthParameterType */
        $fourthParameterType = $reflection->getParameters()[3]->getType();

        if (is_a($fourthParameterType->getName(), Field::class, true) === false) {
            throw new PhpInvalidArgumentException('Тип четвёртого параметра не соответствует ожидаемому.');
        }

        if ($reflection->hasReturnType()) {
            $returnTypeName = $reflection->getReturnType()->getName();

            if (
                in_array($returnTypeName, ['string', 'bool'], true) === false
                && is_a($returnTypeName, EntityError::class, true) === false
            ) {
                throw new PhpInvalidArgumentException('Возвращаемый тип не соответствует ожидаемому.');
            }
        }

        $this->validateCallback = $validateCallback;
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-param mixed $value
     * @psalm-param mixed $primary
     * @psalm-param array<mixed> $row
     */
    public function validate($value, $primary, array $row, Field $field)
    {
        // @phpstan-ignore-next-line why:dynamic-typing
        return call_user_func_array($this->validateCallback, [$value, $primary, $row, $field]);
    }
}
