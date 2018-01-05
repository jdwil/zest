<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\FacetedTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;
use JDWil\Zest\Facet\Enumeration;
use JDWil\Zest\Facet\FractionDigits;
use JDWil\Zest\Facet\Length;
use JDWil\Zest\Facet\MaxExclusive;
use JDWil\Zest\Facet\MaxInclusive;
use JDWil\Zest\Facet\MaxLength;
use JDWil\Zest\Facet\MinExclusive;
use JDWil\Zest\Facet\MinInclusive;
use JDWil\Zest\Facet\MinLength;
use JDWil\Zest\Facet\Pattern;
use JDWil\Zest\Facet\TotalDigits;
use JDWil\Zest\Facet\WhiteSpace;

/**
 * Class Restriction
 */
class Restriction extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface, FacetedInterface
{
    use IdentifiableTrait, AnyAttributeTrait, FacetedTrait;

    /**
     * @var string
     */
    protected $base;

    /**
     * @var SimpleType|null
     */
    protected $simpleType;

    /**
     * @var Group|null
     */
    protected $group;

    /**
     * @var All|null
     */
    protected $all;

    /**
     * @var Choice|null
     */
    protected $choice;

    /**
     * @var Sequence|null
     */
    protected $sequence;

    /**
     * @var Attribute[]
     */
    protected $attributes;

    /**
     * @var AttributeGroup[]
     */
    protected $attributeGroups;

    /**
     * @var AnyAttribute|null
     */
    protected $anyAttribute;

    /**
     * @param \DOMElement $e
     * @param AbstractElement|null $parent
     * @return Restriction
     * @throws InvalidSchemaException
     * @throws \JDWil\Zest\Exception\ValidationException
     */
    public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null): Restriction
    {
        $ret = new static;
        $ret->facets = [];
        $ret->attributes = [];
        $ret->attributeGroups = [];
        $ret->load($e, $parent);

        foreach ($e->attributes as $key => $value) {
            switch ($key) {
                case 'id':
                    $ret->id = $value->value;
                    break;
                case 'base':
                    $ret->base = $value->value;
                    break;
                default:
                    $ret->otherAttributes[$key] = $value->value;
                    break;
            }
        }

        foreach ($ret->children as $child) {
            if (!$ret->isElementType($child)) {
                continue;
            }

            switch ($child->localName) {
                case 'annotation':
                    $ret->annotation = $child->textContent;
                    break;

                case 'simpleType':
                    $ret->simpleType = SimpleType::fromDomElement($child, $ret);
                    break;

                case 'minExclusive':
                    $ret->facets[] = new MinExclusive($child->getAttribute('value'));
                    break;

                case 'minInclusive':
                    $ret->facets[] = new MinInclusive($child->getAttribute('value'));
                    break;

                case 'maxExclusive':
                    $ret->facets[] = new MaxExclusive($child->getAttribute('value'));
                    break;

                case 'maxInclusive':
                    $ret->facets[] = new MaxInclusive($child->getAttribute('value'));
                    break;

                case 'totalDigits':
                    $ret->facets[] = new TotalDigits($child->getAttribute('value'));
                    break;

                case 'fractionDigits':
                    $ret->facets[] = new FractionDigits($child->getAttribute('value'));
                    break;

                case 'length':
                    $ret->facets[] = new Length($child->getAttribute('value'));
                    break;

                case 'minLength':
                    $ret->facets[] = new MinLength($child->getAttribute('value'));
                    break;

                case 'maxLength':
                    $ret->facets[] = new MaxLength($child->getAttribute('value'));
                    break;

                case 'enumeration':
                    $ret->facets[] = new Enumeration($child->getAttribute('value'));
                    break;

                case 'whiteSpace':
                    $ret->facets[] = new WhiteSpace($child->getAttribute('value'));
                    break;

                case 'pattern':
                    $ret->facets[] = new Pattern($child->getAttribute('value'));
                    break;

                case 'group':
                    $ret->group = Group::fromDomElement($child, $ret);
                    break;

                case 'all':
                    $ret->all = All::fromDomElement($child, $ret);
                    break;

                case 'choice':
                    $ret->choice = Choice::fromDomElement($child, $ret);
                    break;

                case 'sequence':
                    $ret->sequence = Sequence::fromDomElement($child, $ret);
                    break;

                case 'attribute':
                    $ret->attributes[] = Attribute::fromDomElement($child, $ret);
                    break;

                case 'attributeGroup':
                    $ret->attributeGroups[] = AttributeGroup::fromDomElement($child, $ret);
                    break;

                case 'anyAttribute':
                    $ret->anyAttribute = AnyAttribute::fromDomElement($child, $ret);
                    break;

                default:
                    throw new InvalidSchemaException('Bad child in restriction: ' . $child->localName, $e);
            }
        }

        if (null === $ret->base) {
            throw new InvalidSchemaException('base attribute is required for restrictions', $e);
        }

        return $ret;
    }

    /**
     * @return string
     */
    public function getBase(): string
    {
        return $this->base;
    }

    /**
     * @return SimpleType|null
     */
    public function getSimpleType()
    {
        return $this->simpleType;
    }

    /**
     * @return Group|null
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @return All|null
     */
    public function getAll()
    {
        return $this->all;
    }

    /**
     * @return Choice|null
     */
    public function getChoice()
    {
        return $this->choice;
    }

    /**
     * @return Sequence|null
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * @return Attribute[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return AttributeGroup[]
     */
    public function getAttributeGroups(): array
    {
        return $this->attributeGroups;
    }

    /**
     * @return AnyAttribute|null
     */
    public function getAnyAttribute()
    {
        return $this->anyAttribute;
    }
}
