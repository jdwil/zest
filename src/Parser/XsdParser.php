<?php
declare(strict_types=1);

namespace JDWil\Zest\Parser;

use JDWil\Xsd\Context\ContextInterface;
use JDWil\Xsd\Entity\Schema;

class XsdParser
{
    private $processedSchemas;
    private $nodes;

    public function __construct()
    {
        $this->processedSchemas = [];
        $this->nodes = [];
    }

    /**
     * @param string $pathToXsd
     * @return array
     */
    public function parseXsdFile(string $pathToXsd): array
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->load($pathToXsd);
        $this->processElement($document->documentElement);

        return $this->nodes;
    }

    /**
     * @param \DOMElement $node
     * @return array
     */
    private function processElement(\DOMElement $node, array $currentSchema = [], bool $store = false)
    {
        $schemaLevel = false;

        $nodeArray = [
            'type' => $node->nodeName,
            'schema' => empty($currentSchema) ? null : $currentSchema['schemaName'],
            'attributes' => [],
            'children' => []
        ];

        if ($node->localName === 'schema') {
            if (!$currentSchema = $this->processSchema($node)) {
                return;
            }

            $schemaLevel = true;
        } else if ($node->localName === 'import') {
            $this->processImport($node);
        } else {
            /** @var \DOMAttr $attribute */
            foreach ($node->attributes as $attribute) {
                $nodeArray['attributes'][$attribute->name] = $attribute->value;
            }
        }

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $childNode) {
                if ($childNode instanceof \DOMElement) {
                    $nodeArray['children'][] = $this->processElement($childNode, $currentSchema, $schemaLevel);
                }
            }
        }

        if ($store) {
            $this->nodes[] = $nodeArray;
        }

        return $nodeArray;
    }

    /**
     * @param \DOMElement $schema
     * @return array|null
     */
    private function processSchema(\DOMElement $schema)
    {
        $schemaArray = [
            'name' => 'schema',
            'schemaName' => null,
            'childSchemas' => []
        ];

        $xpath = new \DOMXPath($schema->ownerDocument);
        foreach ($xpath->query('namespace::*') as $nsNode) {
            /** @var \DOMNameSpaceNode $nsNode */
            if ($nsNode->localName === 'xmlns') {
                $schemaArray['schemaName'] = $nsNode->nodeValue;
            } else {
                $schemaArray['childSchemas'][$nsNode->localName] = $nsNode->nodeValue;
            }
        }

        if (!in_array($schemaArray['schemaName'], $this->processedSchemas, true)) {
            $this->processedSchemas[] = $schemaArray['schemaName'];

            return $schemaArray;
        }

        return null;
    }

    private function processImport(\DOMElement $import)
    {
        $location = $import->getAttribute('schemaLocation');
        $baseUri = $import->baseURI;
        $pieces = explode('/', $baseUri);
        array_pop($pieces);
        $pieces[] = $location;
        $uri = implode('/', $pieces);

        $document = new \DOMDocument('1.0', 'UTF-8');
        if (!$document->load($uri)) {
            throw new \Exception(
                sprintf('Could not load schema file: %s', $uri)
            );
        }

        $this->processElement($document->documentElement);
    }
}
