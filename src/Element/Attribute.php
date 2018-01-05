<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use bar\baz\source_with_namespace;
use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;
use JDWil\Zest\XsdType\QName;

class Attribute extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

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
     * @var string
     */
    protected $use;

    /**
     * @var SimpleType|null
     */
    protected $simpleType;

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
        $ret->use = 'optional';
        if ($form = $ret->getSchema()->getAttributeFormDefault()) {
            $ret->form = $form;
        }

        foreach ($e->attributes as $key => $value) {
            switch ($key) {
                case 'default':
                    $ret->default = $value->value;
                    break;

                case 'fixed':
                    $ret->fixed = $value->value;
                    break;

                case 'form':
                    $ret->form = $value->value;
                    break;

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

                case 'use':
                    $ret->use = $value->value;
                    break;

                default:
                    $ret->otherAttributes[$key] = $value->value;
                    break;
            }
        }

        if (null !== $ret->default && null !== $ret->fixed) {
            throw new InvalidSchemaException('The default and fixed fields of an attribute cannot both be present', $e);
        }

        if (null !== $ret->form && !\in_array($ret->form, ['qualified', 'unqualified'], true)) {
            throw new InvalidSchemaException('form attribute of attribute must be one of qualified or unqualified', $e);
        }

        if (null !== $ret->name && null !== $ret->ref) {
            throw new InvalidSchemaException('The name and ref fields of an attribute cannot both be present', $e);
        }

        foreach ($ret->children as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }

            switch ($child->localName) {
                case 'annotation':
                    // handled in parent
                    break;

                case 'simpleType':
                    $ret->simpleType = SimpleType::fromDomElement($child, $ret);
                    break;

                default:
                    throw new InvalidSchemaException('Bad element in attribute: ' . $child->localName, $child);
            }
        }

        if (null !== $ret->simpleType && null !== $ret->type) {
            throw new InvalidSchemaException('The type attribute of attribute can only be used when no simpleType child is present', $e);
        }

        return $ret;
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
     * @return string
     */
    public function getUse(): string
    {
        return $this->use;
    }

    /**
     * @return SimpleType|null
     */
    public function getSimpleType()
    {
        return $this->simpleType;
    }
}
