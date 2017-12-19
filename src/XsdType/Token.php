<?php
declare(strict_types=1);

namespace JDWil\Zest\XsdType;

/**
 * Class Token
 */
class Token extends AbstractStringType
{
    /**
     * Token constructor.
     *
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = (string) trim(preg_replace('/([\\n\\r\\t])|(\\s{2,})/', ' ', $value));
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return (string) $this->value;
    }
}

