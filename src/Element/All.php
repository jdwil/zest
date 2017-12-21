<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;

/**
 * Class All
 */
class All extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

    /**
     * @var int
     */
    protected $maxOccurs;

    /**
     * @var int
     */
    protected $minOccurs;

    /**
     * @var Element[]
     */
    protected $elements;

    /**
     * @param \DOMElement $e
     * @param AbstractElement $parent
     * @return mixed
     * @throws \JDWil\Zest\Exception\InvalidSchemaException
     */
    public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null)
    {
        $ret = new static;
        $ret->load($e, $parent);

        $ret->elements = [];
        $ret->maxOccurs = 1;
        $ret->minOccurs = 1;

        foreach ($e->attributes as $key => $value) {
            switch ($key) {
                case 'id':
                    $ret->id = $value->value;
                    break;

                case 'maxOccurs':
                    // maxOccurs can only be 1
                    break;

                case 'minOccurs':
                    $ret->minOccurs = (int) $value->value;
                    break;

                default:
                    $ret->otherAttributes[$key] = $value->value;
                    break;
            }
        }

        if (!\in_array($ret->minOccurs, [0, 1], true)) {
            throw new InvalidSchemaException('minOccurs in All must be 0 or 1', $e);
        }

        foreach ($ret->children as $child) {
            switch ($child->localName) {
                case 'annotation':
                    // handled in parent
                    break;

                case 'element':
                    $ret->elements[] = Element::fromDomElement($child, $ret);
                    break;

                default:
                    throw new InvalidSchemaException('Bad element in All: ' . $child->localName, $e);
            }
        }

        return $ret;
    }

    /**
     * @return int
     */
    public function getMaxOccurs(): int
    {
        return $this->maxOccurs;
    }

    /**
     * @return int
     */
    public function getMinOccurs(): int
    {
        return $this->minOccurs;
    }

    /**
     * @return Element[]
     */
    public function getElements(): array
    {
        return $this->elements;
    }
}
