<?php
declare(strict_types=1);

namespace JDWil\Zest\XsdType;

/**
 * Class XString
 */
class XString extends AbstractStringType
{
    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
