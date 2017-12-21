<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;

/**
 * Class Notation
 */
class Notation extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $public;

    /**
     * @var string|null
     */
    protected $system;

    /**
     * @param \DOMElement $e
     * @param AbstractElement $parent
     * @return Notation
     * @throws \JDWil\Zest\Exception\InvalidSchemaException
     */
    public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null): Notation
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

                case 'public':
                    $ret->public = $value->value;
                    break;

                case 'system':
                    $ret->system = $value->value;
                    break;

                default:
                    $ret->otherAttributes[$key] = $value->value;
                    break;
            }
        }

        if (null === $ret->name) {
            throw new InvalidSchemaException('name is required on notation', $e);
        }

        if (null === $ret->public) {
            throw new InvalidSchemaException('public is required on notation', $e);
        }

        return $ret;
    }
}
