<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;

/**
 * Class List_
 */
class List_ extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

    /**
     * @var string
     */
    protected $itemType;

    /**
     * @var SimpleType|null
     */
    protected $simpleType;

    /**
     * @param \DOMElement $e
     * @param AbstractElement|null $parent
     * @return List_
     * @throws InvalidSchemaException
     */
    public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null): List_
    {
        $ret = new static;
        $ret->load($e, $parent);

        if ($itemType = $e->getAttribute('itemType')) {
            $ret->itemType = $itemType;
        }

        foreach ($ret->children as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }

            switch ($child->localName) {
                case 'annotation':
                    continue 2;

                case 'simpleType':
                    $ret->simpleType = SimpleType::fromDomElement($child, $ret);
                    break;

                default:
                    throw new InvalidSchemaException('Bad child in list: ' . $child->localName, $child);
            }
        }

        if (null === $ret->itemType && null === $ret->simpleType) {
            throw new InvalidSchemaException('One of itemType or simpleType must be provided to a list', $e);
        }

        if (null !== $ret->itemType && null !== $ret->simpleType) {
            throw new InvalidSchemaException('itemType is not allowed in a list when simpleType is provided', $e);
        }

        return $ret;
    }
}
