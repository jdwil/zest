<?php
declare(strict_types=1);

namespace JDWil\Zest\Model;

use JDWil\Zest\Element\Schema;
use JDWil\Zest\Exception\InvalidSchemaException;

/**
 * Class SchemaCollection
 */
class SchemaCollection
{
    /**
     * @var Schema[]
     */
    protected $schemas;

    /**
     * SchemaCollection constructor.
     */
    public function __construct()
    {
        $this->schemas = [];
    }

    /**
     * @param Schema $schema
     */
    public function addSchema(Schema $schema)
    {
        $this->schemas[] = $schema;
    }

    /**
     * @return Schema[]
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }

    /**
     * @param string $xmlns
     * @return Schema|false
     */
    public function getSchemaByXmlns(string $xmlns)
    {
        foreach ($this->schemas as $schema) {
            if ($schema->getXmlns() === $xmlns) {
                return $schema;
            }
        }

        return false;
    }
}
