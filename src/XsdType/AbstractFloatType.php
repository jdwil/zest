<?php
declare(strict_types=1);

namespace JDWil\Zest\XsdType;

/**
 * Class AbstractFloatType
 */
abstract class AbstractFloatType implements FloatTypeInterface
{
    /**
     * @var float
     */
    public $value;

    /**
     * @return float
     */
    public function getValue() : float
    {
        return $this->value;
    }
}

