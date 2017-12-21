<?php
declare(strict_types=1);

namespace JDWil\Zest\Exception;
use Throwable;

/**
 * Class InvalidSchemaException
 */
class InvalidSchemaException extends \Exception
{
    public function __construct(string $message, \DOMElement $e = null)
    {
        if (null !== $e) {
            $message .= "\nIn " . $e->baseURI . ':' . $e->getLineNo() . "\n";
        }
        parent::__construct($message);
    }
}
