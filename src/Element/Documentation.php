<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Exception\InvalidSchemaException;

/**
 * Class Documentation
 */
class Documentation extends AbstractElement
{
    /**
     * @var string|null
     */
    protected $source;

    /**
     * @var string|null
     */
    protected $xmlLang;

    /**
     * @param \DOMElement $e
     * @param AbstractElement $parent
     * @return Documentation
     * @throws \JDWil\Zest\Exception\InvalidSchemaException
     */
    public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null): Documentation
    {
        $ret = new static;
        $ret->load($e, $parent);

        foreach ($e->attributes as $key => $value) {
            switch ($key) {
                case 'source':
                    $ret->source = $value->value;
                    break;

                case 'xml:lang':
                case 'lang':
                    $ret->xmlLang = $value->value;
                    break;

                default:
                    throw new InvalidSchemaException('Bad attribute in documentation: ' . $key, $e);
            }
        }

        return $ret;
    }

    /**
     * @return null|string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return null|string
     */
    public function getXmlLang()
    {
        return $this->xmlLang;
    }
}
