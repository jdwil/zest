<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;
use JDWil\Zest\XsdType\NonNegativeInteger;

/**
 * Class Sequence
 */
class Sequence extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

    /**
     * @var NonNegativeInteger|false false is 'unbounded'
     */
    protected $maxOccurs;

    /**
     * @var NonNegativeInteger
     */
    protected $minOccurs;

    /**
     * @var Element[]
     */
    protected $elements;

    /**
     * @var Group[]
     */
    protected $groups;

    /**
     * @var Choice[]
     */
    protected $choices;

    /**
     * @var Sequence[]
     */
    protected $sequences;

    /**
     * @var Any[]
     */
    protected $any;

    /**
     * @param \DOMElement $e
     * @param AbstractElement $parent
     * @return Sequence
     * @throws \JDWil\Zest\Exception\InvalidSchemaException
     * @throws \JDWil\Zest\Exception\ValidationException
     */
    public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null): Sequence
    {
        $ret = new static;
        $ret->load($e, $parent);
        $ret->maxOccurs = new NonNegativeInteger(1);
        $ret->minOccurs = new NonNegativeInteger(1);
        $ret->elements = [];
        $ret->groups = [];
        $ret->choices = [];
        $ret->sequences = [];
        $ret->any = [];

        foreach ($e->attributes as $key => $value) {
            switch ($key) {
                case 'id':
                    $ret->id = $value->value;
                    break;

                case 'maxOccurs':
                    $ret->maxOccurs = $value->value === 'unbounded' ? false : new NonNegativeInteger((int) $value->value);
                    break;

                case 'minOccurs':
                    $ret->minOccurs = new NonNegativeInteger((int) $value->value);
                    break;

                default:
                    $ret->otherAttributes[$key] = $value->value;
                    break;
            }
        }

        $index = 0;
        foreach ($ret->children as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }

            switch ($child->localName) {
                case 'annotation':
                    // handled in parent
                    break;

                case 'element':
                    $ret->elements[$index++] = Element::fromDomElement($child, $ret);
                    break;

                case 'group':
                    $ret->groups[$index++] = Group::fromDomElement($child, $ret);
                    break;

                case 'choice':
                    $ret->choices[$index++] = Choice::fromDomElement($child, $ret);
                    break;

                case 'sequence':
                    $ret->sequences[$index++] = self::fromDomElement($child, $ret);
                    break;

                case 'any':
                    $ret->any[$index++] = Any::fromDomElement($child, $ret);
                    break;

                default:
                    throw new InvalidSchemaException('Bad element in sequence: ' . $child->localName, $child);
            }
        }

        return $ret;
    }

    /**
     * @return array
     */
    public function getChildElements(): array
    {
        return array_replace([], $this->elements, $this->groups, $this->choices, $this->sequences, $this->any);
    }

    /**
     * @return false|NonNegativeInteger
     */
    public function getMaxOccurs()
    {
        return $this->maxOccurs;
    }

    /**
     * @return NonNegativeInteger
     */
    public function getMinOccurs(): NonNegativeInteger
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

    /**
     * @return Group[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @return Choice[]
     */
    public function getChoices(): array
    {
        return $this->choices;
    }

    /**
     * @return Sequence[]
     */
    public function getSequences(): array
    {
        return $this->sequences;
    }

    /**
     * @return Any[]
     */
    public function getAny(): array
    {
        return $this->any;
    }
}
