<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\XsdType\AnyUri;
use JDWil\Zest\XsdType\Token;

/**
 * Class Schema
 */
class Schema extends AbstractElement implements IdentifiableInterface, AnyAttributeInterface
{
    use IdentifiableTrait, AnyAttributeTrait;

    /**
     * @var 'qualified'|'unqualified'
     */
    protected $attributeFormDefault;

    /**
     * @var 'qualified'|'unqualified'
     */
    protected $elementFormDefault;

    /**
     * @var string
     */
    protected $blockDefault;

    /**
     * @var string
     */
    protected $finalDefault;

    /**
     * @var AnyUri
     */
    protected $targetNamespace;

    /**
     * @var Token
     */
    protected $version;

    /**
     * @var AnyUri
     */
    protected $xmlns;

    /**
     * @var array
     */
    protected $importAlias;

    /**
     * @var SimpleType[]
     */
    protected $simpleTypes;

    /**
     * @var Import[]
     */
    protected $imports;

    /**
     * @param \DOMElement $e
     * @return Schema
     * @throws \JDWil\Zest\Exception\InvalidSchemaException
     * @throws \JDWil\Zest\Exception\ValidationException
     */
    public static function fromDomElement(\DOMElement $e): Schema
    {
        $ret = new static;
        $ret->imports = [];
        $ret->simpleTypes = [];
        $ret->load($e);

        $xpath = new \DOMXPath($e->ownerDocument);
        foreach ($xpath->query('namespace::*') as $nsNode) {
            /** @var \DOMNameSpaceNode $nsNode */
            if ($nsNode->localName !== 'xmlns') {
                $ret->importAlias[$nsNode->prefix] = $nsNode->namespaceURI;
            } else {
                $ret->xmlns = $nsNode->namespaceURI;
            }
        }

        /**
         * @var string $key
         * @var \DOMAttr $value
         */
        foreach ($e->attributes as $key => $value) {
            switch ($key) {
                case 'id':
                    $ret->id = $value->value;
                    break;

                case 'attributeFormDefault':
                    $ret->attributeFormDefault = $value->value;
                    break;

                case 'elementFormDefault':
                    $ret->elementFormDefault = $value->value;
                    break;

                case 'blockDefault':
                    $ret->blockDefault = $value->value;
                    break;

                case 'finalDefault':
                    $ret->finalDefault = $value->value;
                    break;

                case 'targetNamespace':
                    $ret->targetNamespace = new AnyUri($value->value);
                    break;

                case 'version':
                    $ret->version = new Token($value->value);
                    break;

                case 'xmlns':
                    $ret->xmlns = new AnyUri($value->value);
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
                case 'simpleType':
                    $ret->simpleTypes[] = SimpleType::fromDomElement($child);
                    break;

                case 'import':
                    $ret->imports[] = Import::fromDomElement($child);
                    break;

                default:
                    //echo "Unhandled\n";
                    break;
            }
        }

        return $ret;
    }

    /**
     * @return string
     */
    public function getXmlns(): string
    {
        return (string) $this->xmlns;
    }

    /**
     * @return SimpleType[]
     */
    public function getSimpleTypes(): array
    {
        return $this->simpleTypes;
    }

    /**
     * @return Import[]
     */
    public function getImports(): array
    {
        return $this->imports;
    }
}
