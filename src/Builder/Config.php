<?php
declare(strict_types=1);

namespace JDWil\Zest\Builder;

use JDWil\PhpGenny\Type\Class_;

class Config
{
    /**
     * @var string
     */
    public $namespacePrefix;

    /**
     * @var string
     */
    public $outputDir;

    /**
     * @var string
     */
    public $xsdTypeNamespacePrefix = 'XsdType';

    /**
     * @var string
     */
    public $zestNamespacePrefix = 'Zest';

    /**
     * @var bool
     */
    public $generateFluidSetters = true;

    /**
     * @var bool
     */
    public $generateFluidBuilder = true;

    public function __construct(string $namespacePrefix)
    {
        $this->namespacePrefix = $namespacePrefix;
    }

    /**
     * @return string
     */
    public function getZestClassNamespace(): string
    {
        $parts = [];
        if ('' !== $this->namespacePrefix) {
            $parts[] = $this->namespacePrefix;
        }
        $parts[] = $this->zestNamespacePrefix;

        return implode('\\', $parts);
    }

    /**
     * @return string
     */
    public function getXsdClassNamespace(): string
    {
        $parts = [];
        if ('' !== $this->namespacePrefix) {
            $parts[] = $this->namespacePrefix;
        }
        $parts[] = $this->xsdTypeNamespacePrefix;

        return implode('\\', $parts);
    }
}
