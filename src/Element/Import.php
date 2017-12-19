<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\XsdType\AnyUri;

/**
 * Class Import
 */
class Import extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

    /**
     * @var AnyUri|null
     */
    protected $namespace;

    /**
     * @var AnyUri|null
     */
    protected $schemaLocation;

    /**
     * @param \DOMElement $e
     * @return Import
     * @throws \JDWil\Zest\Exception\ValidationException
     */
    public static function fromDomElement(\DOMElement $e): Import
    {
        $ret = new static;
        $ret->load($e);

        foreach ($e->attributes as $key => $value) {
            switch ($key) {
                case 'namespace':
                    $ret->namespace = new AnyUri($value->value);
                    break;

                case 'schemaLocation':
                    $ret->schemaLocation = new AnyUri($value->value);
                    break;

                default:
                    $ret->otherAttributes[$key] = $value->value;
                    break;
            }
        }

        return $ret;
    }

    /**
     * @return AnyUri|null
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return AnyUri|null
     */
    public function getSchemaLocation()
    {
        return $this->schemaLocation;
    }
}
