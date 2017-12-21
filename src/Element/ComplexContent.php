<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;

class ComplexContent extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

    /**
     * @var bool
     */
    protected $mixed;

    /**
     * @var Restriction|null
     */
    protected $restriction;

    /**
     * @var Extension|null
     */
    protected $extension;

    /**
     * @param \DOMElement $e
     * @param AbstractElement $parent
     * @return ComplexContent
     * @throws \JDWil\Zest\Exception\InvalidSchemaException
     */
    public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null): ComplexContent
    {
        $ret = new static;
        $ret->load($e, $parent);
        $ret->mixed = false;

        foreach ($e->attributes as $key => $value) {
            switch ($key) {
                case 'id':
                    $ret->id = $value->value;
                    break;

                case 'mixed':
                    $ret->mixed = $value->value === 'true';
                    break;

                default:
                    $ret->otherAttributes[$key] = $value->value;
                    break;
            }
        }

        foreach ($ret->children as $child) {
            switch ($child->localName) {
                case 'annotation':
                    // handled in parent
                    break;

                case 'restriction':
                    $ret->restriction = Restriction::fromDomElement($child, $ret);
                    break;

                case 'extension':
                    $ret->extension = Extension::fromDomElement($child, $ret);
                    break;

                default:
                    throw new InvalidSchemaException('Bad element in ComplexContent: ' . $child->localName, $child);
            }
        }

        if (null === $ret->restriction && null === $ret->extension) {
            throw new InvalidSchemaException('One of restriction or extension must be supplied to ComplexContent');
        }

        return $ret;
    }

    /**
     * @return bool
     */
    public function isMixed(): bool
    {
        return $this->mixed;
    }

    /**
     * @return Restriction|null
     */
    public function getRestriction()
    {
        return $this->restriction;
    }

    /**
     * @return Extension|null
     */
    public function getExtension()
    {
        return $this->extension;
    }
}
