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
     * @var Class_
     */
    public $validationExceptionClass;

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

        $ec = new Class_('ValidationException');
        $ec->setExtends('\\Exception');
        $ec->setNamespace($namespacePrefix);
        $this->validationExceptionClass = $ec;
    }
}
