<?php
declare(strict_types=1);

namespace JDWil\Zest\XsdType;

/**
 * Class XFloat
 */
class XFloat extends AbstractFloatType
{
    /**
     * @param float $value
     */
    public function __construct(float $value)
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
