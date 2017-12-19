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
            $message .= "\nDOM Element:\n" . print_r($e, true);
        }
        parent::__construct($message);
    }
}
