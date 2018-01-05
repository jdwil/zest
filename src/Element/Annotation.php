<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;

/**
 * Class Annotation
 */
class Annotation extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

    /**
     * @var Appinfo[]
     */
    protected $appinfos;

    /**
     * @var Documentation[]
     */
    protected $documentations;

    /**
     * @param \DOMElement $e
     * @param AbstractElement $parent
     * @return Annotation
     * @throws \JDWil\Zest\Exception\InvalidSchemaException
     */
    public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null): Annotation
    {
        $ret = new static;
        $ret->load($e, $parent);

        foreach ($e->attributes as $key => $value) {
            switch ($key) {
                case 'id':
                    $ret->id = $value->value;
                    break;

                default:
                    $ret->otherAttributes[$key] = $value->value;
                    break;
            }
        }

        foreach ($ret->children as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }

            switch ($child->localName) {
                case 'appinfo':
                    $ret->appinfos[] = Appinfo::fromDomElement($child, $ret);
                    break;

                case 'documentation':
                    $ret->documentations[] = Documentation::fromDomElement($child, $ret);
                    break;

                default:
                    throw new InvalidSchemaException('Bad element in annotation: ' . $child->localName, $child);
            }
        }

        return $ret;
    }
}
