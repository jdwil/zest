<?php
declare(strict_types=1);

namespace JDWil\Zest\Util;

class NamespaceUtil
{
    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var array
     */
    protected $schemas;

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
        $this->schemas = [];
    }

    /**
     * @param string $schema
     * @return string
     */
    public function schemaToNamespace(string $schema): string
    {
        $parts = explode('/', parse_url($schema)['path']);
        array_shift($parts);
        $parts = array_map(function (string $name) {
            return ucwords($name);
        }, $parts);

        return implode('\\', $parts);
    }

    /**
     * @param string $schema
     */
    public function addSchema(string $schema)
    {
        $this->schemas[$schema] = [];
    }

    /**
     * @param string $schema
     * @param string $alias
     * @param string $full
     */
    public function addMapping(string $schema, string $alias, string $full)
    {
        $this->schemas[$schema][$alias] = $full;
    }
}
