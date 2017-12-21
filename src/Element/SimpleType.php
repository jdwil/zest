<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;

/**
 * Class SimpleType
 */
class SimpleType extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var Restriction|null
     */
    protected $restriction;

    /**
     * @var List_|null
     */
    protected $list;

    /**
     * @var Union|null
     */
    protected $union;

    /**
     * @param \DOMElement $e
     * @param AbstractElement $parent
     * @return SimpleType
     * @throws InvalidSchemaException
     */
    public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null): SimpleType
    {
        $ret = new static;
        $ret->load($e, $parent);

        foreach ($e->attributes as $name => $value) {
            switch ($name) {
                case 'id':
                    $ret->id = $value->value;
                    break;
                case 'name':
                    $ret->name = $value->value;
                    break;
                default:
                    $ret->otherAttributes[$name] = $value->value;
                    break;
            }
        }

        foreach ($ret->children as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }

            switch ($child->localName) {
                case 'restriction':
                    $ret->restriction = Restriction::fromDomElement($child, $ret);
                    break;

                case 'union':
                    $ret->union = Union::fromDomElement($child, $ret);
                    break;

                case 'list':
                    $ret->list = List_::fromDomElement($child, $ret);
                    break;

                default:
                    throw new InvalidSchemaException('Bad child in simpleType: ' . $child->localName, $e);
            }
        }

        if (null === $ret->name && $ret->childOfSchema()) {
            throw new InvalidSchemaException('name is required in a simpleType that is the child of a schema', $e);
        }

        if (null !== $ret->name && !$ret->childOfSchema()) {
            throw new InvalidSchemaException('name is not allowed in simpleType unless it is the child of a schema', $e);
        }

        return $ret;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Restriction|null
     */
    public function getRestriction()
    {
        return $this->restriction;
    }

    /**
     * @return List_|null
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @return Union|null
     */
    public function getUnion()
    {
        return $this->union;
    }
}
