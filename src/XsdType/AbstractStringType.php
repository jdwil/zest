<?php
declare(strict_types=1);

namespace JDWil\Zest\XsdType;

/**
 * Class AbstractStringType
 */
abstract class AbstractStringType implements StringTypeInterface
{
    /**
     * @var string
     */
    public $value;

    /**
     * @return string
     */
    public function getValue() : string
    {
        return $this->value;
    }
}

