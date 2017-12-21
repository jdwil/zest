<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;
use JDWil\Zest\XsdType\NonNegativeInteger;

class Any extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

    /**
     * @var NonNegativeInteger|false
     */
    protected $maxOccurs;

    /**
     * @var NonNegativeInteger
     */
    protected $minOccurs;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var string
     */
    protected $processContents;

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
        $ret->maxOccurs = new NonNegativeInteger(1);
        $ret->minOccurs = new NonNegativeInteger(1);
        $ret->namespace = '##any';
        $ret->processContents = 'strict';

        foreach ($e->attributes as $key => $value) {
            switch ($key) {
                case 'id':
                    $ret->id = $value->value;
                    break;

                case 'maxOccurs':
                    $ret->maxOccurs = $value->value === 'unbounded' ? false : new NonNegativeInteger((int) $value->value);
                    break;

                case 'minOccurs':
                    $ret->minOccurs = new NonNegativeInteger((int) $value->value);
                    break;

                case 'namespace':
                    $ret->namespace = $value->value;
                    break;

                case 'processContents':
                    $ret->processContents = $value->value;
                    break;

                default:
                    $ret->otherAttributes[$key] = $value->value;
                    break;
            }
        }

        if (!\in_array($ret->processContents, ['strict', 'lax', 'skip'], true)) {
            throw new InvalidSchemaException('processContents attribute of anyAttribute must be one of strict, lax or skip', $e);
        }

        $list = false;
        if (strpos($ret->namespace, ' ') !== false) {
            $list = true;
        }

        if (!$list && !\in_array($ret->namespace, ['##any', '##other', '##local', '##targetNamespace'], true)) {
            throw new InvalidSchemaException('namespace attribute of anyAttribute must be one of ##any, ##other, ##local, ##targetNamespace or a list of namespaces', $e);
        }

        return $ret;
    }

    /**
     * @return false|NonNegativeInteger
     */
    public function getMaxOccurs()
    {
        return $this->maxOccurs;
    }

    /**
     * @return NonNegativeInteger
     */
    public function getMinOccurs(): NonNegativeInteger
    {
        return $this->minOccurs;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function getProcessContents(): string
    {
        return $this->processContents;
    }
}
