<?php

namespace JDWil\Zest\XsdType;

/**
 * Interface BoolTypeInterface
 */
interface BoolTypeInterface
{
    /**
     * @param bool $value
     */
    public function __construct(bool $value);

    /**
     * @return string
     */
    public function __toString() : string;

    /**
     * @return bool
     */
    public function getValue() : bool;
}

