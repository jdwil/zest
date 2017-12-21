<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;
use JDWil\Zest\XsdType\QName;

/**
 * Class Extension
 */
class Extension extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

    /**
     * @var QName
     */
    protected $base;

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
     * @param AbstractElement $parent
     * @return mixed
     * @throws \JDWil\Zest\Exception\ValidationException
     * @throws \JDWil\Zest\Exception\InvalidSchemaException
     */
    public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null)
    {
        $ret = new static;
        $ret->load($e, $parent);

        foreach ($e->attributes as $key => $value) {
            switch ($key) {
                case 'id':
                    $ret->id = $value->value;
                    break;

                case 'base':
                    $ret->base = new QName($value->value);
                    break;

                default:
                    $ret->otherAttributes[$key] = $value->value;
                    break;
            }
        }

        if (null === $ret->base) {
            throw new InvalidSchemaException('base is required on extension', $e);
        }

        foreach ($ret->children as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }

            switch ($child->localName) {
                case 'annotation':
                    // handled in parent
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
                    throw new InvalidSchemaException('Bad element in extension: ' . $child->localName, $child);
            }
        }

        return $ret;
    }

    /**
     * @return QName
     */
    public function getBase(): QName
    {
        return $this->base;
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
