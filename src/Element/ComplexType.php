<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;
use Phlexy\Lexer\Stateful\Simple;

class ComplexType extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $abstract;

    /**
     * @var bool
     */
    protected $mixed;

    /**
     * @var string
     */
    protected $block;

    /**
     * @var string
     */
    protected $final;

    /**
     * @var SimpleContent|null
     */
    protected $simpleContent;

    /**
     * @var ComplexContent|null
     */
    protected $complexContent;

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
     * @return ComplexType
     * @throws \JDWil\Zest\Exception\ValidationException
     * @throws InvalidSchemaException
     */
    public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null): ComplexType
    {
        $ret = new static;
        $ret->load($e, $parent);
        $ret->attributes = [];
        $ret->attributeGroups = [];

        foreach ($e->attributes as $name => $value) {
            switch ($name) {
                case 'id':
                    $ret->id = $value->value;
                    break;
                case 'name':
                    $ret->name = $value->value;
                    break;
                case 'abstract':
                    $ret->abstract = (bool) $value->value === 'true';
                    break;
                case 'mixed':
                    $ret->mixed = (bool) $value->value === 'true';
                    break;
                case 'block':
                    $ret->block = $value->value;
                    break;
                case 'final':
                    $ret->final = $value->value;
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
                case 'annotation':
                    // Handled in parent
                    break;

                case 'simpleContent':
                    $ret->simpleContent = SimpleContent::fromDomElement($child, $ret);
                    break;

                case 'complexContent':
                    $ret->complexContent = ComplexContent::fromDomElement($child, $ret);
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
                    throw new InvalidSchemaException('Bad child in simpleType: ' . $child->localName, $e);
            }
        }

        return $ret;
    }
}
