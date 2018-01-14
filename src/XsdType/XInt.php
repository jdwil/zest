<?php
declare(strict_types=1);

namespace JDWil\Zest\XsdType;

/**
 * Class XInt
 */
class XInt extends AbstractIntegerType
{
    /**
     * @param int $value
     */
    public function __construct(int $value)
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
