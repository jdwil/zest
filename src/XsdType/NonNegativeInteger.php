<?php

namespace JDWil\Zest\XsdType;

use JDWil\Zest\Exception\ValidationException;


/**
 * Class NonNegativeInteger
 */
class NonNegativeInteger extends AbstractIntegerType
{
    /**
     * NonNegativeInteger constructor.
     *
     * @param int $value
     * @throws ValidationException
     */
    public function __construct(int $value)
    {
        if (!$this->isNonNegativeInteger($value)) {
            $this->throwNotValid();
        }

        $this->value = $value;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return (string) $this->value;
    }

    /**
     * @return int
     */
    public function getValue() : int
    {
        return $this->value;
    }

    /**
     * @param int $value
     * @return bool
     */
    private function isNonNegativeInteger(int $value): bool
    {
        return $value >= 0;
    }

    /**
     * @throws ValidationException
     */
    private function throwNotValid()
    {
        throw new ValidationException('value must be >= 0');
    }
}

