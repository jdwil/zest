<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;

/**
 * Class Include_
 */
class Include_ extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

    /**
     * @var string
     */
    protected $schemaLocation;

    /**
     * @param \DOMElement $e
     * @param AbstractElement $parent
     * @return Include_
     * @throws \JDWil\Zest\Exception\InvalidSchemaException
     */
    public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null): Include_
    {
        $ret = new static;
        $ret->load($e, $parent);

        foreach ($e->attributes as $key => $value) {
            switch ($key) {
                case 'id':
                    $ret->id = $value->value;
                    break;

                case 'schemaLocation':
                    $ret->schemaLocation = $value->value;
                    break;

                default:
                    $ret->otherAttributes[$key] = $value->value;
                    break;
            }
        }

        if (null === $ret->schemaLocation) {
            throw new InvalidSchemaException('schemaLocation is required on include', $e);
        }

        return $ret;
    }

    /**
     * @return string
     */
    public function getSchemaLocation(): string
    {
        return $this->schemaLocation;
    }
}
