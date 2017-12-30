<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;
use JDWil\Zest\XsdType\QName;

class Union extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

    /**
     * @var QName[]
     */
    protected $memberTypes;

    /**
     * @var SimpleType[]
     */
    protected $simpleTypes;

    /**
     * @param \DOMElement $e
     * @param AbstractElement|null $parent
     * @return Union
     * @throws InvalidSchemaException
     */
    public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null): Union
    {
        $ret = new static;
        $ret->load($e, $parent);
        $ret->simpleTypes = [];
        $ret->memberTypes = [];

        if ($memberTypes = $e->getAttribute('memberTypes')) {
            foreach (explode(' ', $memberTypes) as $memberType) {
                $ret->memberTypes[] = new QName($memberType);
            }
        }

        foreach ($ret->children as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }

            switch ($child->localName) {
                case 'annotation':
                    continue 2;

                case 'simpleType':
                    $ret->simpleTypes[] = SimpleType::fromDomElement($child, $ret);
                    break;

                default:
                    throw new InvalidSchemaException('Bad child in union: ' . $child->localName, $e);
            }
        }

        return $ret;
    }

    /**
     * @return QName[]
     */
    public function getMemberTypes(): array
    {
        return $this->memberTypes;
    }

    /**
     * @return SimpleType[]
     */
    public function getSimpleTypes(): array
    {
        return $this->simpleTypes;
    }
}
