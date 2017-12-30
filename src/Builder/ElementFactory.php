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

class ElementFactory
{
    /**
     * @var Class_[]
     */
    protected $classes;

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

    public function __construct(Config $config)
    {
        $this->classes = [];
        $this->validationException = $config->validationExceptionClass;

        $this->xsdTypeFactory = new XsdTypeFactory($config);
        $this->zestClassFactory = new ZestClassFactory($config);
        $this->simpleTypeFactory = new SimpleTypeFactory($config, $this->xsdTypeFactory, $this->zestClassFactory);
        $this->complexTypeFactory = new ComplexTypeFactory(
            $config,
            $this->xsdTypeFactory,
            $this->simpleTypeFactory,
            $this->zestClassFactory
        );
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
            $base = $element->resolveQNameToElement($type);

            if ($base instanceof ComplexType) {
                $baseClass = $this->complexTypeFactory->buildComplexType($base);
            } else if ($base instanceof SimpleType) {
                $baseClass = $this->simpleTypeFactory->buildSimpleType($base);
            } else if (!$baseType = TypeUtil::mapXsdTypeToInternalType($type)) {
                $baseClass = TypeUtil::mapXsdTypeToInternalXsdType($type, $this->xsdTypeFactory);
            }
        }

        if ($name = $element->getName()) {
            $c = new Class_($name);
            $c->setNamespace(NamespaceUtil::schemaToNamespace($element->getSchemaNamespace()));
            $c->implements($this->zestClassFactory->buildStreamableInterface());
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
