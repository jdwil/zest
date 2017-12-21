<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

use JDWil\Zest\Element\Traits\AnyAttributeTrait;
use JDWil\Zest\Element\Traits\IdentifiableTrait;
use JDWil\Zest\Exception\InvalidSchemaException;
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
     * @var ComplexType[]
     */
    protected $complexTypes;

    /**
     * @var Schema[]
     */
    protected $alias;

    /**
     * @var Import[]
     */
    protected $imports;

    /**
     * @var Include_[]
     */
    protected $includes;

    /**
     * @var Redefine[]
     */
    protected $redefines;

    /**
     * @var Group[]
     */
    protected $groups;

    /**
     * @var AttributeGroup[]
     */
    protected $attributeGroups;

    /**
     * @var Element[]
     */
    protected $elements;

    /**
     * @var Attribute[]
     */
    protected $attributes;

    /**
     * @var Notation[]
     */
    protected $notations;

    /**
     * @param \DOMElement $e
     * @param AbstractElement $parent
     * @return Schema
     * @throws \JDWil\Zest\Exception\ValidationException
     * @throws \JDWil\Zest\Exception\InvalidSchemaException
     */
    public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null): Schema
    {
        $ret = new static;
        $ret->imports = [];
        $ret->simpleTypes = [];
        $ret->load($e, $parent);

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
                case 'annotation':
                    // handled in parent
                    break;

                case 'simpleType':
                    $ret->simpleTypes[] = SimpleType::fromDomElement($child, $ret);
                    break;

                case 'complexType':
                    $ret->complexTypes[] = ComplexType::fromDomElement($child, $ret);
                    break;

                case 'include':
                    $ret->includes[] = Include_::fromDomElement($child, $ret);
                    break;

                case 'redefine':
                    $ret->redefines[] = Redefine::fromDomElement($child, $ret);
                    break;

                case 'group':
                    $ret->groups[] = Group::fromDomElement($child, $ret);
                    break;

                case 'attributeGroup':
                    $ret->attributeGroups[] = AttributeGroup::fromDomElement($child, $ret);
                    break;

                case 'element':
                    $ret->elements[] = Element::fromDomElement($child, $ret);
                    break;

                case 'attribute':
                    $ret->attributes[] = Attribute::fromDomElement($child, $ret);
                    break;

                case 'notation':
                    $ret->notations[] = Notation::fromDomElement($child, $ret);
                    break;

                case 'import':
                    $ret->imports[] = Import::fromDomElement($child, $ret);
                    break;

                default:
                    throw new InvalidSchemaException('Bad element in schema: ' . $child->localName, $child);
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

    /**
     * @param string $alias
     * @param Schema $schema
     */
    public function addAlias(string $alias, Schema $schema)
    {
        $this->alias[$alias] = $schema;
    }

    /**
     * @param string $alias
     * @return bool
     */
    public function hasAlias(string $alias): bool
    {
        return isset($this->alias[$alias]);
    }

    /**
     * @param string $alias
     * @return Schema
     */
    public function getAlias(string $alias): Schema
    {
        return $this->alias[$alias];
    }

    /**
     * @param Schema $schema
     * @throws InvalidSchemaException
     */
    public function resolveSchemaAlias(Schema $schema)
    {
        foreach ($this->importAlias as $prefix => $namespace) {
            if ($namespace === $schema->getXmlns()) {
                $this->addAlias($prefix, $schema);

                return;
            }
        }

        // No alias provided in schema doc
    }

    /**
     * @return mixed
     */
    public function getAttributeFormDefault()
    {
        return $this->attributeFormDefault;
    }

    /**
     * @return mixed
     */
    public function getElementFormDefault()
    {
        return $this->elementFormDefault;
    }

    /**
     * @return string
     */
    public function getBlockDefault(): string
    {
        return $this->blockDefault;
    }

    /**
     * @return string
     */
    public function getFinalDefault(): string
    {
        return $this->finalDefault;
    }

    /**
     * @return AnyUri
     */
    public function getTargetNamespace(): AnyUri
    {
        return $this->targetNamespace;
    }

    /**
     * @return Token
     */
    public function getVersion(): Token
    {
        return $this->version;
    }

    /**
     * @return array
     */
    public function getImportAlias(): array
    {
        return $this->importAlias;
    }

    /**
     * @return ComplexType[]
     */
    public function getComplexTypes(): array
    {
        return $this->complexTypes;
    }

    /**
     * @return Include_[]
     */
    public function getIncludes(): array
    {
        return $this->includes;
    }

    /**
     * @return Redefine[]
     */
    public function getRedefines(): array
    {
        return $this->redefines;
    }

    /**
     * @return Group[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @return AttributeGroup[]
     */
    public function getAttributeGroups(): array
    {
        return $this->attributeGroups;
    }

    /**
     * @return Element[]
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    /**
     * @return Attribute[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return Notation[]
     */
    public function getNotations(): array
    {
        return $this->notations;
    }
}
