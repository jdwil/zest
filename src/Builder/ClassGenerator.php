<?php
declare(strict_types=1);

namespace JDWil\Zest\Builder;

use JDWil\Zest\Element\Schema;
use JDWil\Zest\Model\SchemaCollection;

class ClassGenerator
{
    /**
     * @var XsdTypeFactory
     */
    protected $xsdFactory;

    /**
     * @var SimpleTypeFactory
     */
    protected $simpleTypeFactory;

    public function __construct(
        XsdTypeFactory $xsdFactory,
        SimpleTypeFactory $simpleTypeFactory
    ) {
        $this->xsdFactory = $xsdFactory;
        $this->simpleTypeFactory = $simpleTypeFactory;
    }

    public function buildClasses(SchemaCollection $schemas)
    {
        $ret = [];

        foreach ($schemas->getSchemas() as $schema) {
            foreach ($schema->getSimpleTypes() as $simpleType) {
                $ret[] = $this->simpleTypeFactory->buildSimpleType($simpleType);
            }
        }

        return $ret;
    }
}
