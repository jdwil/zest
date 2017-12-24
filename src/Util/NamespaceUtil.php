<?php
declare(strict_types=1);

namespace JDWil\Zest\Util;

class NamespaceUtil
{
    /**
     * @param string $schema
     * @return string
     */
    public static function schemaToNamespace(string $schema): string
    {
        $parts = explode('/', parse_url($schema)['path']);
        array_shift($parts);
        $parts = array_map(function (string $name) {
            return ucwords($name);
        }, $parts);

        return implode('\\', $parts);
    }
}
