<?php

namespace JDWil\Zest\XsdType;

/**
 * Interface FloatTypeInterface
 */
interface FloatTypeInterface
{
    /**
     * @param float $value
     */
    public function __construct(float $value);

    /**
     * @return string
     */
    public function __toString() : string;

    /**
     * @return float
     */
    public function getValue() : float;
}

