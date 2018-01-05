<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;
use JDWil\Zest\XsdType\NonNegativeInteger;
use JDWil\Zest\XsdType\QName;

/**
 * Class Element
 */
class Element extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
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
     * @var QName|null
     */
    protected $type;

    /**
     * @var QName|null
     */
    protected $substitutionGroup;

    /**
     * @var string|null
     */
    protected $default;

    /**
     * @var string|null
     */
    protected $fixed;

    /**
     * @var string|null
     */
    protected $form;

    /**
     * @var NonNegativeInteger|false|null
     */
    protected $maxOccurs;

    /**
     * @var NonNegativeInteger|null
     */
    protected $minOccurs;

    /**
     * @var bool
     */
    protected $nillable;

    /**
     * @var bool
     */
    protected $abstract;

    /**
     * @var string|null
     */
    protected $block;

    /**
     * @var string|null
     */
    protected $final;

    /**
     * @var SimpleType|null
     */
    protected $simpleType;

    /**
     * @var ComplexType|null
     */
    protected $complexType;

    /**
     * @var Unique[]
     */
    protected $unique;

    /**
     * @var Key[]
     */
    protected $keys;

    /**
     * @var Keyref[]
     */
    protected $keyrefs;

    /**
     * @param \DOMElement $e
     * @param AbstractElement $parent
     * @return Element
     * @throws \JDWil\Zest\Exception\InvalidSchemaException
     * @throws \JDWil\Zest\Exception\ValidationException
     */
    public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null): Element
    {
        $ret = new static;
        $ret->unique = [];
        $ret->keys = [];
        $ret->keyrefs = [];
        $ret->load($e, $parent);
        if ($form = $ret->getSchema()->getElementFormDefault()) {
            $ret->form = $form;
        }
        $ret->nillable = false;
        $ret->abstract = false;

        if (!$parent instanceof Schema) {
            $ret->maxOccurs = new NonNegativeInteger(1);
            $ret->minOccurs = new NonNegativeInteger(1);
        }

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

                case 'type':
                    $ret->type = new QName($value->value);
                    break;

                case 'substitutionGroup':
                    $ret->substitutionGroup = new QName($value->value);
                    break;

                case 'default':
                    $ret->default = $value->value;
                    break;

                case 'fixed':
                    $ret->fixed = $value->value;
                    break;

                case 'form':
                    if ($parent instanceof Schema) {
                        throw new InvalidSchemaException('form cannot be used on an element whose parent is a schema', $e);
                    }

                    $ret->form = $value->value;
                    break;

                case 'maxOccurs':
                    $ret->maxOccurs = $value->value === 'unbounded' ? false : new NonNegativeInteger((int) $value->value);
                    break;

                case 'minOccurs':
                    $ret->minOccurs = new NonNegativeInteger((int) $value->value);
                    break;

                case 'nillable':
                    $ret->nillable = $value->value === 'true';
                    break;

                case 'abstract':
                    $ret->abstract = $value->value === 'true';
                    break;

                case 'block':
                    $ret->block = $value->value;
                    break;

                case 'final':
                    $ret->final = $value->value;
                    break;

                default:
                    $ret->otherAttributes[$key] = $value->value;
                    break;
            }
        }

        if ($parent instanceof Schema && null === $ret->name) {
            throw new InvalidSchemaException('name is required on an element whose parent is a schema', $e);
        }

        if ($parent instanceof Schema && null !== $ret->ref) {
            throw new InvalidSchemaException('ref cannot be used on an element whose parent is a schema', $e);
        }

        if (!$parent instanceof Schema && null !== $ret->substitutionGroup) {
            throw new InvalidSchemaException('substitutionGroup cannot be used if element is not the child of a schema', $e);
        }

        // @todo validate block and final

        foreach ($ret->children as $child) {
            switch ($child->localName) {
                case 'annotation':
                    // handled in parent
                    break;

                case 'simpleType':
                    $ret->simpleType = SimpleType::fromDomElement($child, $ret);
                    break;

                case 'complexType':
                    $ret->complexType = ComplexType::fromDomElement($child, $ret);
                    break;

                case 'unique':
                    $ret->unique[] = Unique::fromDomElement($child, $ret);
                    break;

                case 'key':
                    $ret->keys[] = Key::fromDomElement($child, $ret);
                    break;

                case 'keyref':
                    $ret->keyrefs[] = Keyref::fromDomElement($child, $ret);
                    break;

                default:
                    throw new InvalidSchemaException('Bad element in element: ' . $child->localName, $child);
            }
        }

        if (    null === $ret->simpleType &&
                (null !== $ret->complexType || !empty($ret->unique) || !empty($ret->keys) || !empty($ret->keyrefs)) &&
                null !== $ret->default
        ) {
            throw new InvalidSchemaException('default can only be used on an element whose content is simpleType or text only', $e);
        }

        if (    null === $ret->simpleType &&
                (null !== $ret->complexType || !empty($ret->unique) || !empty($ret->keys) || !empty($ret->keyrefs)) &&
                null !== $ret->fixed
        ) {
            throw new InvalidSchemaException('fixed can only be used on an element whose content is simpleType or text only', $e);
        }

        if ($parent instanceof Schema && null !== $ret->maxOccurs) {
            throw new InvalidSchemaException('maxOccurs cannot be used on an element whose parent is a schema', $e);
        }

        if ($parent instanceof Schema && null !== $ret->minOccurs) {
            throw new InvalidSchemaException('minOccurs cannot be used on an element whose parent is a schema', $e);
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
     * @return QName|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return null|QName
     */
    public function getSubstitutionGroup()
    {
        return $this->substitutionGroup;
    }

    /**
     * @return null|string
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @return null|string
     */
    public function getFixed()
    {
        return $this->fixed;
    }

    /**
     * @return null|string
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @return false|NonNegativeInteger|null
     */
    public function getMaxOccurs()
    {
        return $this->maxOccurs;
    }

    /**
     * @return NonNegativeInteger|null
     */
    public function getMinOccurs()
    {
        return $this->minOccurs;
    }

    /**
     * @return bool
     */
    public function isNillable(): bool
    {
        return $this->nillable;
    }

    /**
     * @return bool
     */
    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    /**
     * @return null|string
     */
    public function getBlock()
    {
        return $this->block;
    }

    /**
     * @return null|string
     */
    public function getFinal()
    {
        return $this->final;
    }

    /**
     * @return SimpleType|null
     */
    public function getSimpleType()
    {
        return $this->simpleType;
    }

    /**
     * @return ComplexType|null
     */
    public function getComplexType()
    {
        return $this->complexType;
    }

    /**
     * @return Unique[]
     */
    public function getUnique(): array
    {
        return $this->unique;
    }

    /**
     * @return Key[]
     */
    public function getKeys(): array
    {
        return $this->keys;
    }

    /**
     * @return Keyref[]
     */
    public function getKeyrefs(): array
    {
        return $this->keyrefs;
    }

    /**
     * @param false|NonNegativeInteger|null $maxOccurs
     */
    public function setMaxOccurs($maxOccurs)
    {
        $this->maxOccurs = $maxOccurs;
    }

    /**
     * @param NonNegativeInteger|null $minOccurs
     */
    public function setMinOccurs(NonNegativeInteger $minOccurs)
    {
        $this->minOccurs = $minOccurs;
    }

    /**
     * @param null|string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }
}
