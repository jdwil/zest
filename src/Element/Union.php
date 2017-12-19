<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;

class Union extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

    /**
     * @var string[]
     */
    protected $memberTypes;

    /**
     * @var SimpleType[]
     */
    protected $simpleTypes;

    /**
     * @param \DOMElement $e
     * @return Union
     * @throws \JDWil\Zest\Exception\InvalidSchemaException
     */
    public static function fromDomElement(\DOMElement $e): Union
    {
        $ret = new static;
        $ret->load($e);

        if ($memberTypes = $e->getAttribute('memberTypes')) {
            $ret->memberTypes = explode(' ', $memberTypes);
        }

        foreach ($ret->children as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }

            switch ($child->localName) {
                case 'annotation':
                    continue 2;

                case 'simpleType':
                    $ret->simpleTypes[] = SimpleType::fromDomElement($child);
                    break;

                default:
                    throw new InvalidSchemaException('Bad child in union: ' . $child->localName, $e);
            }
        }

        return $ret;
    }
}
