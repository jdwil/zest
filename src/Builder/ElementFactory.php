<?php
declare(strict_types=1);

namespace JDWil\Zest\Builder;

use JDWil\PhpGenny\Builder\Node\Reference;
use JDWil\PhpGenny\Builder\Node\Scalar;
use JDWil\PhpGenny\Builder\Node\Variable;
use JDWil\PhpGenny\Type\Class_;
use JDWil\PhpGenny\Type\Interface_;
use JDWil\Zest\Element\ComplexType;
use JDWil\Zest\Element\Element;
use JDWil\Zest\Element\SimpleType;
use JDWil\Zest\Util\NamespaceUtil;
use JDWil\Zest\Util\TypeUtil;
use Symfony\Component\Console\Output\OutputInterface;

class ElementFactory
{
    /**
     * @var Class_[]
     */
    protected $classes;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Class_
     */
    protected $validationException;

    /**
     * @var XsdTypeFactory
     */
    protected $xsdTypeFactory;

    /**
     * @var SimpleTypeFactory
     */
    protected $simpleTypeFactory;

    /**
     * @var ComplexTypeFactory
     */
    protected $complexTypeFactory;

    /**
     * @var ZestClassFactory
     */
    protected $zestClassFactory;

    public function __construct(Config $config, OutputInterface $output)
    {
        $this->classes = [];
        $this->config = $config;

        $this->zestClassFactory = new ZestClassFactory($config);
        $this->xsdTypeFactory = new XsdTypeFactory($config, $this->zestClassFactory);
        $this->simpleTypeFactory = new SimpleTypeFactory($config, $this->xsdTypeFactory, $this->zestClassFactory);
        $this->complexTypeFactory = new ComplexTypeFactory(
            $config,
            $this->xsdTypeFactory,
            $this->simpleTypeFactory,
            $this->zestClassFactory,
            $this,
            $output
        );

        $this->validationException = $this->zestClassFactory->buildValidationException();
    }

    /**
     * @param Element $element
     * @return Class_
     * @throws \Exception
     * @throws \JDWil\Zest\Exception\InvalidSchemaException
     */
    public function buildElement(Element $element): Class_
    {
        if ($type = $element->getType()) {
            $base = $this->complexTypeFactory->resolveElementType($element);

            if ($base instanceof ComplexType) {
                $baseClass = $this->complexTypeFactory->buildComplexType($base);
            } else if ($base instanceof SimpleType) {
                $baseClass = $this->simpleTypeFactory->buildSimpleType($base);
            } else if ($base instanceof Class_ || $base instanceof Interface_) {
                $baseClass = $base;
            }
        }

        if ($name = $element->getName()) {
            $c = new Class_($name);
            $c->setNamespace(NamespaceUtil::schemaToNamespace(
                $element->getSchemaNamespace(),
                $this->config->namespacePrefix
            ));
            $c->implements($this->zestClassFactory->buildStreamableInterface());

            if (isset($this->classes[$c->getFqn()])) {
                return $this->classes[$c->getFqn()];
            }

            $this->classes[$c->getFqn()] = $c;
        }

        if (isset($c)) {
            if (isset($baseClass)) {
                $c->setExtends($baseClass);
                $c->getMethodByName('writeToStream')->getBody()->execute(
                    Reference::parent()->staticCall('writeToStream', Variable::named('stream'), Scalar::string($element->getName()))
                );
            }

            if ($simpleType = $element->getSimpleType()) {
                $this->simpleTypeFactory->processSimpleType($simpleType, $c);
            } else if ($complexType = $element->getComplexType()) {
                $this->complexTypeFactory->processComplexType($complexType, $c);
            }
        }

        return $c;
    }

    /**
     * @return Class_[]|Interface_[]
     */
    public function getClasses(): array
    {
        $ret = $this->classes;

        $ret = array_merge($ret, $this->simpleTypeFactory->getClasses());
        $ret = array_merge($ret, $this->complexTypeFactory->getClasses());
        $ret = array_merge($ret, $this->xsdTypeFactory->getClasses());
        $ret = array_merge($ret, $this->xsdTypeFactory->getInterfaces());
        $ret = array_merge($ret, $this->zestClassFactory->getClasses());
        $ret = array_merge($ret, $this->zestClassFactory->getInterfaces());

        return $ret;
    }
}
