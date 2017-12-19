<?php
declare(strict_types=1);

namespace JDWil\Zest\XsdType;

/**
 * Interface StringTypeInterface
 */
interface StringTypeInterface
{
    /**
     * @param string $value
     */
    public function __construct(string $value);

    /**
     * @return string
     */
    public function __toString() : string;

    /**
     * @return string
     */
    public function getValue() : string;
}

