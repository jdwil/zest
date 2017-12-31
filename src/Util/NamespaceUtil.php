<?php
declare(strict_types=1);

namespace JDWil\Zest\Util;

class NamespaceUtil
{
    /**
     * @param string $schema
     * @param string $prefix
     * @return string
     */
    public static function schemaToNamespace(string $schema, string $prefix = ''): string
    {
        $parts = explode('/', parse_url($schema)['path']);
        array_shift($parts);
        $parts = array_map(function (string $name) {
            return ucwords($name);
        }, $parts);

        if ('' !== $prefix) {
            array_unshift($parts, $prefix);
        }

        return implode('\\', $parts);
    }
}
