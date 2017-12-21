<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Exception\InvalidSchemaException;

class Appinfo extends AbstractElement
{

    /**
     * @var string|null
     */
    protected $source;

    /**
     * @param \DOMElement $e
     * @param AbstractElement $parent
     * @return Appinfo
     * @throws \JDWil\Zest\Exception\InvalidSchemaException
     */
    public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null): Appinfo
    {
        $ret = new static;
        $ret->load($e, $parent);

        foreach ($e->attributes as $key => $value) {
            switch ($key) {
                case 'source':
                    $ret->source = $value->value;
                    break;

                default:
                    throw new InvalidSchemaException('Bad attribute in appinfo: ' . $key, $e);
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
}
