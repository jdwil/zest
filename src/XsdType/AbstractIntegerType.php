<?php

namespace JDWil\Zest\XsdType;

use JDWil\Zest\XsdType\IntegerTypeInterface;


/**
 * Class AbstractIntegerType
 */
abstract class AbstractIntegerType implements IntegerTypeInterface
{
    /**
     * @var int
     */
    public $value;
    
    /**
     * @return int
     */
    public function getValue() : int
    {
        return $this->value;
    }
}

