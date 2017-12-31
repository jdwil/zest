<?php
declare(strict_types=1);

namespace JDWil\Zest\Builder;

use JDWil\PhpGenny\Builder\Node\AbstractNode;
use JDWil\PhpGenny\Builder\Node\Cast;
use JDWil\PhpGenny\Builder\Node\If_;
use JDWil\PhpGenny\Builder\Node\Logic;
use JDWil\PhpGenny\Builder\Node\NewInstance;
use JDWil\PhpGenny\Builder\Node\Node;
use JDWil\PhpGenny\Builder\Node\Reference;
use JDWil\PhpGenny\Builder\Node\ResultOf;
use JDWil\PhpGenny\Builder\Node\Scalar;
use JDWil\PhpGenny\Builder\Node\Type;
use JDWil\PhpGenny\Builder\Node\Variable;
use JDWil\PhpGenny\Type\Class_;
use JDWil\PhpGenny\Type\Method;
use JDWil\PhpGenny\Type\Parameter;
use JDWil\PhpGenny\Type\Property;
use JDWil\PhpGenny\ValueObject\InternalType;
use JDWil\PhpGenny\ValueObject\Visibility;
use JDWil\Zest\Element\AbstractElement;
use JDWil\Zest\Element\List_;
use JDWil\Zest\Element\Restriction;
use JDWil\Zest\Element\SimpleType;
use JDWil\Zest\Exception\InvalidSchemaException;
use JDWil\Zest\Facet\Enumeration;
use JDWil\Zest\Facet\FractionDigits;
use JDWil\Zest\Facet\Length;
use JDWil\Zest\Facet\MaxExclusive;
use JDWil\Zest\Facet\MaxInclusive;
use JDWil\Zest\Facet\MaxLength;
use JDWil\Zest\Facet\MinExclusive;
use JDWil\Zest\Facet\MinInclusive;
use JDWil\Zest\Facet\MinLength;
use JDWil\Zest\Facet\Pattern;
use JDWil\Zest\Facet\TotalDigits;
use JDWil\Zest\Facet\WhiteSpace;
use JDWil\Zest\Util\NamespaceUtil;
use JDWil\Zest\Util\TypeUtil;
use JDWil\Zest\XsdType\QName;

class SimpleTypeFactory
{
    const VALUE_VAR = 'value';

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
    protected $xsdFactory;

    /**
     * @var ZestClassFactory
     */
    protected $zestClassFactory;

    public function __construct(Config $config, XsdTypeFactory $xsdFactory, ZestClassFactory $zestClassFactory)
    {
        $this->classes = [];
        $this->config = $config;
        $this->xsdFactory = $xsdFactory;
        $this->zestClassFactory = $zestClassFactory;

        $this->validationException = $zestClassFactory->buildValidationException();
    }

    /**
     * @param string $fqn
     * @return false|Class_
     */
    public function getClass(string $fqn) {
        return $this->classes[$fqn] ?? false;
    }

    /**
     * @param SimpleType $simpleType
     * @return Class_
     * @throws \Exception
     */
    public function buildSimpleType(SimpleType $simpleType): Class_
    {
        $c = new Class_($simpleType->getName());
        $c->setNamespace(NamespaceUtil::schemaToNamespace(
            $simpleType->getSchemaNamespace(),
            $this->config->namespacePrefix
        ));

        if (isset($this->classes[$c->getFqn()])) {
            return $this->classes[$c->getFqn()];
        }
        $this->classes[$c->getFqn()] = $c;

        $c->addProperty(new Property(self::VALUE_VAR, Visibility::isPrivate()));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter(self::VALUE_VAR, InternalType::mixed()));
        $c->addMethod($constructor);

        $getValue = new Method('getValue');
        $getValue->getBody()->return(Variable::named('this')->property(self::VALUE_VAR));
        $c->addMethod($getValue);

        $this->addToStringMethod($c);

        $this->processSimpleType($simpleType, $c);

        $constructor->getBody()->execute(Variable::named('this')->property(self::VALUE_VAR)->equals(Variable::named(self::VALUE_VAR)));

        return $c;
    }

    /**
     * @param SimpleType $simpleType
     * @param $c
     * @param bool $forUnion
     * @throws \Exception
     */
    public function processSimpleType(SimpleType $simpleType, $c, bool $forUnion = false)
    {
        if ($restriction = $simpleType->getRestriction()) {
            $this->addRestrictions($restriction, $c, $forUnion);
        }

        $this->addList($simpleType, $c);
        $this->addUnion($simpleType, $c);
    }

    /**
     * @param Restriction $restriction
     * @param Class_ $c
     * @param bool $forUnion
     * @throws \Exception
     */
    public function addRestrictions(Restriction $restriction, Class_ $c, bool $forUnion = false)
    {
        $validatorMethod = $c->getMethodByName('__construct');
        $variable = Variable::named(self::VALUE_VAR);

        $baseQName = new QName($restriction->getBase());
        if ($type = TypeUtil::mapXsdTypeToInternalType($baseQName)) {
            $this->getValueProperty($c)->setType($type);
            $this->getValueParameter($c)->setType($type);
            $this->getGetValueMethod($c)->setReturnTypes([$type]);
        } else if ($type = TypeUtil::mapXsdTypeToInternalXsdType($baseQName, $this->xsdFactory)) {
            $this->getValueProperty($c)->setType($type);
            $this->getValueParameter($c)->setType($type);
            $this->getGetValueMethod($c)->setReturnTypes([$type]);
        }

        $enumerations = [];

        foreach ($restriction->getFacets() as $facet) {
            if ($facet instanceof Enumeration) {
                $const = 'VALUE_' . strtoupper($facet->getValue());
                $c->addConstant($const, Scalar::string($facet->getValue()));
                $enumerations[] = $const;

            } else if ($facet instanceof FractionDigits) {
                $validatorMethod->getBody()
                    ->if(Cast::toInt($variable)->isNotIdenticalTo($variable))
                        ->execute(Variable::named('decimals')->equals(
                            ResultOf::strlen($variable)
                                ->minus(ResultOf::strpos($variable, Scalar::string('.')))
                                ->minus(Scalar::int(1)))
                        )
                    ->else()
                        ->execute(Variable::named('decimals')->equals(Scalar::int(0)))
                    ->done()
                    ->if(Scalar::int((int) $facet->getValue())->isNotIdenticalTo($variable))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value can only contain ' . $facet->getValue() . ' decimal digits')]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof Length) {
                $validatorMethod->getBody()
                    ->if(Scalar::int((int) $facet->getValue())->isNotIdenticalTo(ResultOf::strlen($variable)))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value must be ' . $facet->getValue() . ' characters')]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof MaxExclusive) {
                $validatorMethod->getBody()
                    ->if($variable->isGreaterThanOrEqualTo(Scalar::int((int) $facet->getValue())))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value out of bounds')]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof MaxInclusive) {
                $validatorMethod->getBody()
                    ->if($variable->isGreaterThan(Scalar::int((int) $facet->getValue())))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value out of bounds')]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof MaxLength) {
                $validatorMethod->getBody()
                    ->if($variable->isGreaterThan(Scalar::int((int) $facet->getValue())))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value must be less than ' . $facet->getValue() . ' characters')]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof MinExclusive) {
                $validatorMethod->getBody()
                    ->if($variable->isLessThanOrEqualTo(Scalar::int((int) $facet->getValue())))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value out of bounds')]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof MinInclusive) {
                $validatorMethod->getBody()
                    ->if($variable->isLessThan(Scalar::int((int) $facet->getValue())))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value out of bounds')]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof MinLength) {
                $validatorMethod->getBody()
                    ->if(Scalar::int((int) $facet->getValue())->isGreaterThan(ResultOf::strlen($variable)))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value must be more than ' . $facet->getValue() . ' characters')]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof Pattern) {
                $pattern = str_replace(['[0-9]', '/'], ['\\d', '\\/'], $facet->getValue());
                $validatorMethod->getBody()
                    ->if(Logic::not(ResultOf::preg_match(Scalar::string('/' . $pattern . '/'), $variable)))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value does not match pattern ' . $pattern)]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof TotalDigits) {
                $validatorMethod->getBody()
                    ->if(Scalar::int((int) $facet->getValue())->isNotIdenticalTo(ResultOf::preg_match_all(Scalar::string('/\d/', $variable))))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value must contain ' . $facet->getValue() . ' digits')]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof WhiteSpace) {
                // @todo handle white space
            }
        }

        if (!empty($enumerations)) {
            if (count($enumerations) > 1) {
                $validatorMethod->getBody()
                    ->if(Logic::not(ResultOf::in_array($variable, Type::array(array_map(function (string $const) {
                        return Reference::self()->constant($const);
                    }, $enumerations)), Type::true())))
                    ->throw(NewInstance::of($this->validationException, [Scalar::string('Bad value for enumeration')]))
                    ->done()
                    ->newLine();
            } else {
                $validatorMethod->getBody()
                    ->if(Reference::self()->constant($enumerations[0])->isNotIdenticalTo($variable))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('Bad value for enumeration')]))
                    ->done()
                    ->newLine()
                ;
            }
        }

        if ($forUnion) {
            $nodes = $validatorMethod->getBody()->getNodes();
            /** @var AbstractNode[] $ifConditions */
            $ifConditions = [];
            foreach ($nodes as $node) {
                if ($node instanceof If_) {
                    $ifConditions[] = $node->getCondition();
                }
            }

            $validatorMethod->getBody()->setNodes([]);
            $ifCondition = null;
            for ($i = 0, $t = \count($ifConditions); $i < $t; $i++) {
                if ($i === 0) {
                    $ifCondition = $ifConditions[$i];
                } else {
                    $ifCondition = $ifCondition->booleanAnd($ifConditions[$i]);
                }
            }
            $validatorMethod->getBody()->if($ifCondition)->throw(NewInstance::of($this->zestClassFactory->buildValidationException(), [Scalar::string('bad value for union')]))->done();
        }
    }

    /**
     * @param SimpleType $simpleType
     * @param Class_ $c
     * @throws \Exception
     */
    private function addUnion(SimpleType $simpleType, Class_ $c)
    {
        if (!$union = $simpleType->getUnion()) {
            return;
        }

        $types = [];
        foreach ($union->getMemberTypes() as $memberType) {
            $types[] = $this->resolveListOrUnionType($union, $memberType);
        }

        if (!empty($types)) {
            if ($types[0] instanceof Class_) {
                $check = Logic::not(Variable::named('v')->instanceOf($types[0]));
            } else if ($types[0] instanceof InternalType) {
                $check = $this->internalTypeToValidation($types[0]);
            }

            for ($i = 1, $count = \count($types); $i < $count; $i++) {
                if ($types[$i] instanceof Class_) {
                    $check = $check->booleanAnd(
                        Logic::not(Variable::named('v')->instanceOf($types[$i]))
                    );
                } else if ($types[$i] instanceof InternalType) {
                    $check = $check->booleanAnd($this->internalTypeToValidation($types[$i]));
                }
            }

            $c->getMethodByName('__construct')->getBody()
                ->foreach(Variable::named(self::VALUE_VAR), Variable::named('v'))
                    ->if($check)
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('Bad type for union')]))
                    ->done()
                ->done()
            ;
        }

        foreach ($union->getSimpleTypes() as $st) {
            $this->processSimpleType($st, $c, $forUnion = true);
        }

        $this->makeParametersVariadic($c, $forUnion = true);
    }

    /**
     * @param SimpleType $simpleType
     * @param Class_ $c
     * @throws \Exception
     */
    private function addList(SimpleType $simpleType, Class_ $c)
    {
        if (!$list = $simpleType->getList()) {
            return;
        }

        if ($itemType = $list->getItemType()) {
            $type = $this->resolveListOrUnionType($list, $itemType);
            $this->getValueProperty($c)->setTypes([$type]);

            if (null !== $type) {
                $check = false;
                if ($type instanceof Class_) {
                    $check = Logic::not(Variable::named('v')->instanceOf($type));
                } else {
                    $check = $this->internalTypeToValidation($type);
                }

                if ($check) {
                    $c->getMethodByName('__construct')->getBody()
                        ->foreach(Variable::named(self::VALUE_VAR), Variable::named('v'))
                            ->if($check)
                                ->throw(NewInstance::of($this->zestClassFactory->buildValidationException(), [Scalar::string('value is wrong type')]))
                            ->done()
                        ->done()
                    ;
                }
            }
        } else {
            $this->processSimpleType($list->getSimpleType(), $c);
        }

        $this->makeParametersVariadic($c);
    }

    /**
     * @param Class_ $c
     * @return Property
     */
    private function getValueProperty(Class_ $c): Property
    {
        foreach ($c->getProperties() as $property) {
            if ($property->getName() === self::VALUE_VAR) {
                return $property;
            }
        }
    }

    /**
     * @param Class_ $c
     * @return Parameter
     */
    private function getValueParameter(Class_ $c): Parameter
    {
        foreach($c->getMethodByName('__construct')->getParameters() as $parameter) {
            if ($parameter->getName() === self::VALUE_VAR) {
                return $parameter;
            }
        }
    }

    /**
     * @param Class_ $c
     * @return Method
     */
    private function getGetValueMethod(Class_ $c): Method
    {
        foreach ($c->getMethods() as $method) {
            if ($method->getName() === 'getValue') {
                return $method;
            }
        }
    }

    /**
     * @param Class_ $c
     */
    private function addToStringMethod(Class_ $c)
    {
        if (!$c->getMethodByName('__toString')) {
            $m = new Method('__toString');
            $m->setReturnTypes([InternalType::string()]);
            $m->getBody()->return(Cast::toString(Variable::named('this')->property(self::VALUE_VAR)));
            $c->addMethod($m);
        }
    }

    /**
     * @return Class_[]
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * @param AbstractElement $element
     * @param QName $qname
     * @return Class_|InternalType|null
     * @throws \Exception
     */
    private function resolveListOrUnionType(AbstractElement $element, QName $qname)
    {
        if ($internalType = TypeUtil::mapXsdTypeToInternalType($qname)) {
            return $internalType;
        } else if ($internalType = TypeUtil::mapXsdTypeToInternalXsdType($qname, $this->xsdFactory)) {
            return $internalType;
        } else if ($elementType = $element->resolveQNameToElement($qname)) {
            if ($elementType instanceof SimpleType) {
                return $this->buildSimpleType($elementType);
            }
        }

        throw new InvalidSchemaException('Invalid element type attribute', $element);
    }

    /**
     * @param Class_ $c
     * @param bool $forUnion
     */
    private function makeParametersVariadic(Class_ $c, bool $forUnion = false)
    {
        $this->getValueParameter($c)->setVariadic(true);
        if ($forUnion) {
            $this->getValueParameter($c)->setType(InternalType::mixed());
            $this->getValueProperty($c)->setTypes([InternalType::array()]);
            $this->getGetValueMethod($c)->setReturnTypes([InternalType::array()]);
        } else {
            $this->getGetValueMethod($c)->setReturnTypes([InternalType::arrayOf($this->getValueProperty($c)->getType())]);
            $this->getValueParameter($c)->setType(InternalType::arrayOf($this->getValueProperty($c)->getType()));
            $this->getValueProperty($c)->setTypes([InternalType::arrayOf($this->getValueProperty($c)->getType())]);
        }
        $toString = $c->getMethodByName('__toString');
        $toString->getBody()->setNodes([]);
        $toString->getBody()->return(ResultOf::implode(Scalar::string(' '), Variable::named('this')->property(self::VALUE_VAR)));
    }

    /**
     * @param $type
     * @return Node
     */
    private function internalTypeToValidation(InternalType $type): Node
    {
        if ($type->isString()) {
            $check = Logic::not(ResultOf::is_string(Variable::named('v')));
        } else if ($type->isInt()) {
            $check = Logic::not(ResultOf::is_int(Variable::named('v')));
        } else if ($type->isFloat()) {
            $check = Logic::not(ResultOf::is_float(Variable::named('v')));
        } else if ($type->isBool()) {
            $check = Logic::not(ResultOf::is_bool(Variable::named('v')));
        }

        if (null === $check) {
            var_dump($type); die();
        }

        return $check;
    }
}
