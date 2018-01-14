<?php
declare(strict_types=1);

namespace JDWil\Zest\XsdType;

/**
 * Class XBool
 */
class XBool extends AbstractBoolType
{
    /**
     * @param bool $value
     */
    public function __construct(bool $value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }
}
