<?php

namespace JDWil\Zest\XsdType;

/**
 * Interface IntegerTypeInterface
 */
interface IntegerTypeInterface
{
    /**
     * @param int $value
     */
    public function __construct(int $value);
    
    /**
     * @return string
     */
    public function __toString() : string;
    
    /**
     * @return int
     */
    public function getValue() : int;
}

