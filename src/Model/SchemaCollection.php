<?php
declare(strict_types=1);

namespace JDWil\Zest\Model;

use JDWil\Zest\Element\Schema;

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
}
