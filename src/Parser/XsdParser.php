<?php
declare(strict_types=1);

namespace JDWil\Zest\Parser;

use JDWil\Zest\Element\Import;
use JDWil\Zest\Element\Schema;
use JDWil\Zest\Exception\InvalidSchemaException;
use JDWil\Zest\Model\SchemaCollection;

class XsdParser
{
    private $processedSchemas;

    /**
     * @var SchemaCollection
     */
    private $schemas;

    public function __construct()
    {
        $this->processedSchemas = [];
        $this->schemas = new SchemaCollection();
    }

    /**
     * @param string $pathToXsd
     * @return SchemaCollection
     * @throws \JDWil\Zest\Exception\ValidationException
     * @throws \JDWil\Zest\Exception\InvalidSchemaException
     * @throws \Exception
     */
    public function parseXsdFile(string $pathToXsd): SchemaCollection
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->load($pathToXsd);
        $this->parseDocument($document);

        return $this->schemas;
    }

    /**
     * @param \DOMDocument $doc
     * @return Schema
     * @throws InvalidSchemaException
     * @throws \JDWil\Zest\Exception\ValidationException
     * @throws \Exception
     */
    protected function parseDocument(\DOMDocument $doc): Schema
    {
        /** @var \DOMElement $child */
        foreach ($doc->childNodes as $child) {
            if ($child instanceof \DOMDocumentType) {
                // @todo do we need to do anything with these?
                continue;
            }

            switch ($child->localName) {
                case 'schema':
                    $schema = Schema::fromDomElement($child);
                    $this->schemas->addSchema($schema);
                    foreach ($schema->getImports() as $import) {
                        if ($childSchema = $this->processImport($import)) {
                            $schema->resolveSchemaAlias($childSchema);
                        }
                    }

                    return $schema;

                default:
                    var_dump($child);
                    throw new InvalidSchemaException('Bad element in document: ' . $child->localName);
                    break;
            }
        }
    }

    /**
     * @param Import $import
     * @return Schema|false
     * @throws \Exception
     */
    private function processImport(Import $import)
    {
        $location = (string) $import->getSchemaLocation();
        if (empty($location)) {
            return false;
        }

        if (\in_array($location, $this->processedSchemas, true)) {
            return $this->schemas->getSchemaByXmlns((string) $import->getNamespace());
        }
        $this->processedSchemas[] = $location;

        if (stripos($location, 'http') === 0) {
            $uri = $location;
        } else {
            $baseUri = $import->getBaseUri();
            $pieces = explode('/', $baseUri);
            array_pop($pieces);
            $pieces[] = $location;
            $uri = implode('/', $pieces);
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        if (!$document->load($uri)) {
            throw new \Exception(
                sprintf('Could not load schema file: %s', $uri)
            );
        }

        return $this->parseDocument($document);
    }

    /**
     * @return SchemaCollection
     */
    public function getSchemas(): SchemaCollection
    {
        return $this->schemas;
    }
}
