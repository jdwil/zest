<?php
declare(strict_types=1);

namespace JDWil\Zest\XsdType;

use JDWil\Zest\Exception\ValidationException;

/**
 * Class AnyUri
 */
class AnyUri extends AbstractStringType
{
    /**
     * AnyUri constructor.
     *
     * @param string $value
     * @throws ValidationException
     */
    public function __construct(string $value)
    {
        if (!$this->isValidUri($value)) {
            $this->throwNotValid();
        }

        $this->value = $value;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return (string) $this->value;
    }

    /**
     * @param string $uri
     * @return bool
     */
    private function isValidUri(string $uri) : bool
    {
        return (bool) preg_match('#(([a-zA-Z][0-9a-zA-Z+\-\.]*:)?/{0,2}[0-9a-zA-Z;/?:@\&=+$\.\-!~*\'()%]+)?(\#[0-9a-zA-Z;/?:@\&=+$\.\-_!~*\'()%]+)?#', $uri);
    }

    /**
     * @throws ValidationException
     */
    private function throwNotValid()
    {
        throw new ValidationException('value must be a valid URI');
    }
}

