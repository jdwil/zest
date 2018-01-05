<?php
declare(strict_types=1);

namespace JDWil\Zest\Builder;

use JDWil\Zest\Model\SchemaCollection;
use Symfony\Component\Console\Output\OutputInterface;

class ClassGenerator
{
    /**
     * @var ElementFactory
     */
    protected $elementFactory;

    public function __construct(Config $config, OutputInterface $output) {
        $this->elementFactory = new ElementFactory($config, $output);
    }

    public function buildClasses(SchemaCollection $schemas)
    {
        $ret = [];

        foreach ($schemas->getSchemas() as $schema) {
            foreach ($schema->getElements() as $element) {
                $ret[] = $this->elementFactory->buildElement($element);
            }
        }

        $ret = array_merge($ret, $this->elementFactory->getClasses());

        return $ret;
    }
}
