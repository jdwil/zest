<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;

/**
 * Class AnyAttribute
 */
class AnyAttribute extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

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
     */
    public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null)
    {
        $ret = new static;
        $ret->load($e, $parent);
        $ret->namespace = '##any';
        $ret->processContents = 'strict';

        foreach ($e->attributes as $key => $value) {
            switch ($key) {
                case 'id':
                    $ret->id = $value->value;
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
