<?php
declare(strict_types=1);

namespace JDWil\Zest\XsdType;

/**
 * Class AbstractBoolType
 */
abstract class AbstractBoolType implements BoolTypeInterface
{
    /**
     * @var bool
     */
    public $value;

    /**
     * @return bool
     */
    public function getValue() : bool
    {
        return $this->value;
    }
}

