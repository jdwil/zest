<?php
declare(strict_types=1);

namespace JDWil\Zest\Element;

/**
 * Class AbstractElement
 */
abstract class AbstractElement
{
    /**
     * @var string
     */
    protected $tagName;

    /**
     * @var string
     */
    protected $schemaTypeInfo;

    /**
     * @var \DOMElement
     */
    protected $parent;

    /**
     * @var AbstractElement
     */
    protected $parentElement;

    /**
     * @var \DOMElement[]
     */
    protected $children;

    /**
     * @var \DOMDocument
     */
    protected $ownerDocument;

    /**
     * @var string
     */
    protected $namespaceUri;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var string
     */
    protected $localName;

    /**
     * @var string
     */
    protected $baseUri;

    /**
     * @var string
     */
    protected $textContent;

    /**
     * @var Annotation[]
     */
    protected $annotations;

    /**
     * @var string
     */
    protected $schemaNamespace;

    /**
     * @var Schema
     */
    protected $schema;

    protected function __construct() {}

    /**
     * @param \DOMElement $e
     * @param AbstractElement $parent
     * @return mixed
     */
    abstract public static function fromDomElement(\DOMElement $e, AbstractElement $parent = null);

    /**
     * @param \DOMElement $e
     * @param AbstractElement $parent
     * @throws \JDWil\Zest\Exception\InvalidSchemaException
     */
    protected function load(\DOMElement $e, AbstractElement $parent = null)
    {
        $this->annotations = [];
        $this->tagName = $e->tagName;
        $this->schemaTypeInfo = $e->schemaTypeInfo;
        $this->parent = $e->parentNode;
        $this->children = $e->childNodes;
        $this->ownerDocument = $e->ownerDocument;
        $this->namespaceUri = $e->namespaceURI;
        $this->prefix = $e->prefix;
        $this->localName = $e->localName;
        $this->baseUri = $e->baseURI;
        $this->textContent = $e->textContent;
        $this->parentElement = $parent;

        if (null !== $parent) {
            $this->schema = $parent instanceof Schema ? $parent : $parent->schema;
        }

        foreach ($this->children as $child) {
            if ($child->localName === 'annotation') {
                // @todo test this
                $this->annotations[] = Annotation::fromDomElement($child, $this);

            }
        }

        if (!$this instanceof Schema) {
            $xpath = new \DOMXPath($e->ownerDocument);
            foreach ($xpath->query('namespace::*') as $nsNode) {
                /** @var \DOMNameSpaceNode $nsNode */
                if ($nsNode->localName === 'xmlns') {
                    $this->schemaNamespace = $nsNode->namespaceURI;
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getSchemaNamespace(): string
    {
        return $this->schemaNamespace;
    }

    /**
     * @return string
     */
    public function getTagName(): string
    {
        return $this->tagName;
    }


    /**
     * @return string
     */
    public function getSchemaTypeInfo(): string
    {
        return $this->schemaTypeInfo;
    }


    /**
     * @return \DOMElement
     */
    public function getParent(): \DOMElement
    {
        return $this->parent;
    }

    /**
     * @return \DOMElement[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return \DOMDocument
     */
    public function getOwnerDocument(): \DOMDocument
    {
        return $this->ownerDocument;
    }

    /**
     * @return string
     */
    public function getNamespaceUri(): string
    {
        return $this->namespaceUri;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @return string
     */
    public function getLocalName(): string
    {
        return $this->localName;
    }

    /**
     * @return string
     */
    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    /**
     * @return string
     */
    public function getTextContent(): string
    {
        return $this->textContent;
    }

    /**
     * @return Annotation[]
     */
    public function getAnnotations(): array
    {
        return $this->annotations;
    }

    /**
     * @return bool
     */
    public function childOfSchema(): bool
    {
        return $this->parent->localName === 'schema';
    }

    /**
     * @return Schema
     */
    public function getSchema(): Schema
    {
        return $this->schema;
    }
}
