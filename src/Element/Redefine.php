<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;

/**
 * Class Redefine
 */
class Redefine extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

    /**
     * @var string
     */
    protected $schemaLocation;

    /**
     * @var SimpleType[]
     */
    protected $simpleTypes;

    /**
     * @var ComplexType[]
     */
    protected $complexTypes;

    /**
     * @var Group[]
     */
    protected $groups;

    /**
     * @var AttributeGroup[]
     */
    protected $attributeGroups;

    /**
     * @param \DOMElement $e
     * @param AbstractElement $parent
     * @return Redefine
     * @throws \JDWil\Zest\Exception\ValidationException
     * @throws \JDWil\Zest\Exception\InvalidSchemaException
     */
    public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null): Redefine
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
            throw new InvalidSchemaException('schemaLocation is required on redefine', $e);
        }

        foreach ($ret->children as $child) {
            switch ($child->localName) {
                case 'annotation':
                    // handled by parent
                    break;

                case 'simpleType':
                    $ret->simpleTypes[] = SimpleType::fromDomElement($child, $ret);
                    break;

                case 'complexType':
                    $ret->complexTypes[] = ComplexType::fromDomElement($child, $ret);
                    break;

                case 'group':
                    $ret->groups[] = Group::fromDomElement($child, $ret);
                    break;

                case 'attributeGroup':
                    $ret->attributeGroups[] = AttributeGroup::fromDomElement($child, $ret);
                    break;

                default:
                    throw new InvalidSchemaException('Bad element in redefine: ' . $child->localName, $child);
            }
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

    /**
     * @return SimpleType[]
     */
    public function getSimpleTypes(): array
    {
        return $this->simpleTypes;
    }

    /**
     * @return ComplexType[]
     */
    public function getComplexTypes(): array
    {
        return $this->complexTypes;
    }

    /**
     * @return Group[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @return AttributeGroup[]
     */
    public function getAttributeGroups(): array
    {
        return $this->attributeGroups;
    }
}
