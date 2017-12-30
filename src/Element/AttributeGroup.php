<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;
use JDWil\Zest\XsdType\QName;

class AttributeGroup extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var QName|null
     */
    protected $ref;

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
     * @throws \JDWil\Zest\Exception\InvalidSchemaException
     */
    public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null)
    {
        $ret = new static;
        $ret->load($e, $parent);
        $ret->attributes = [];
        $ret->attributeGroups = [];

        foreach ($e->attributes as $key => $value) {
            switch ($key) {
                case 'id':
                    $ret->id = $value->value;
                    break;

                case 'name':
                    $ret->name = $value->value;
                    break;

                case 'ref':
                    $ret->ref = new QName($value->value);
                    break;

                default:
                    $ret->otherAttributes[$key] = $value->value;
                    break;
            }
        }

        if (null !== $ret->name && null !== $ret->ref) {
            throw new InvalidSchemaException('name and ref attributes cannot both be set on an AttributeGroup', $e);
        }

        foreach ($ret->children as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }

            switch ($child->localName) {
                case 'annotation':
                    // handled in parent
                    break;

                case 'attribute':
                    $ret->attributes[] = Attribute::fromDomElement($child, $ret);
                    break;

                case 'attributeGroup':
                    $ret->attributeGroups[] = self::fromDomElement($child, $ret);
                    break;

                case 'anyAttribute':
                    $ret->anyAttribute = AnyAttribute::fromDomElement($child, $ret);
                    break;

                default:
                    throw new InvalidSchemaException('Bad element in attributeGroup: ' . $child->localName, $child);
            }
        }

        return $ret;
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return QName|null
     */
    public function getRef()
    {
        return $this->ref;
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
