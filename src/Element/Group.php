<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;
use JDWil\Zest\XsdType\NonNegativeInteger;
use JDWil\Zest\XsdType\QName;

/**
 * Class Group
 */
class Group extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
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
     * @var NonNegativeInteger|false|null false means 'unbounded'
     */
    protected $maxOccurs;

    /**
     * @var NonNegativeInteger|null
     */
    protected $minOccurs;

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
     * @param \DOMElement $e
     * @param AbstractElement $parent
     * @return mixed
     * @throws \JDWil\Zest\Exception\InvalidSchemaException
     * @throws \JDWil\Zest\Exception\ValidationException
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

                case 'ref':
                    $ret->ref = new QName($value->value);
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

        if ($ret->name !== null && !$ret->parentElement instanceof Schema) {
            throw new InvalidSchemaException('If a name is supplied to a Group, it must be the child of a Schema', $e);
        }

        if ($ret->name !== null && $ret->ref !== null) {
            throw new InvalidSchemaException('Name and Ref cannot both be present in a Group', $e);
        }

        foreach ($ret->children as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }

            switch ($child->localName) {
                case 'annotation':
                    // handled in parent
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

                default:
                    throw new InvalidSchemaException('Bad element in Group: ' . $child->localName, $child);
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
}
