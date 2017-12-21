<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;
use JDWil\Zest\XsdType\QName;

class Keyref extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var QName
     */
    protected $refer;

    /**
     * @var Selector|null
     */
    protected $selector;

    /**
     * @var Field[]
     */
    protected $fields;

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

        foreach ($e->attributes as $key => $value) {
            switch ($key) {
                case 'id':
                    $ret->id = $value->value;
                    break;

                case 'name':
                    $ret->name = $value->value;
                    break;

                case 'refer':
                    $ret->refer = new QName($value->value);
                    break;

                default:
                    $ret->otherAttributes[$key] = $value->value;
                    break;
            }
        }

        if (null === $ret->name) {
            throw new InvalidSchemaException('name is required on keyref', $e);
        }

        if (null === $ret->refer) {
            throw new InvalidSchemaException('refer is required on keyref', $e);
        }

        foreach ($ret->children as $child) {
            switch ($child->localName) {
                case 'annotation':
                    // handled in parent
                    break;

                case 'selector':
                    $ret->selector = Selector::fromDomElement($child, $ret);
                    break;

                case 'field':
                    $ret->fields[] = Field::fromDomElement($child, $ret);
                    break;

                default:
                    throw new InvalidSchemaException('Bad element in keyref: ' . $child->localName, $child);
            }
        }

        return $ret;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return QName
     */
    public function getRefer(): QName
    {
        return $this->refer;
    }

    /**
     * @return Selector|null
     */
    public function getSelector()
    {
        return $this->selector;
    }

    /**
     * @return Field[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }
}
