<?php
declare(strict_types=1);

namespace JDWil\Zest\Builder;

use JDWil\PhpGenny\Builder\Node\Cast;
use JDWil\PhpGenny\Builder\Node\Logic;
use JDWil\PhpGenny\Builder\Node\NewInstance;
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
use JDWil\Zest\Element\SimpleType;
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

class SimpleTypeFactory
{
    /**
     * @var Class_[]
     */
    protected $classes;

    /**
     * @var NamespaceUtil
     */
    protected $nsUtil;

    /**
     * @var Method
     */
    protected $constructor;

    /**
     * @var Class_
     */
    protected $validationException;

    /**
     * @var XsdTypeFactory
     */
    protected $xsdFactory;

    /**
     * @var Class_
     */
    protected $c;

    public function __construct(NamespaceUtil $tool, Class_ $validationException, XsdTypeFactory $xsdFactory)
    {
        $this->nsUtil = $tool;
        $this->validationException = $validationException;
        $this->xsdFactory = $xsdFactory;
    }

    /**
     * @param string $fqn
     * @return false|Class_
     */
    public function getClass(string $fqn) {
        return $this->classes[$fqn] ?? false;
    }

    public function buildSimpleType(SimpleType $simpleType): Class_
    {
        $this->c = new Class_($simpleType->getName());
        $this->c->setNamespace($this->nsUtil->schemaToNamespace($simpleType->getSchemaNamespace()));

        if (isset($this->classes[$this->c->getFqn()])) {
            return $this->classes[$this->c->getFqn()];
        }

        $this->c->addProperty(new Property('value', Visibility::isPrivate()));

        $this->constructor = new Method('__construct');
        $this->constructor->addParameter(new Parameter('value', InternalType::mixed()));
        $this->c->addMethod($this->constructor);

        $getValue = new Method('getValue');
        $getValue->getBody()->return(Variable::named('this')->property('value'));
        $this->c->addMethod($getValue);

        $this->addRestrictions($simpleType);

        $this->constructor->getBody()->execute(Variable::named('this')->property('value')->equals(Variable::named('value')));

        $this->classes[$this->c->getFqn()] = $this->c;

        return $this->c;
    }

    private function addRestrictions(SimpleType $simpleType)
    {
        if (!$restriction = $simpleType->getRestriction()) {
            return;
        }

        if ($type = TypeUtil::mapXsdTypeToInternalType($restriction->getBase())) {
            $this->getValueProperty()->setType($type);
            $this->getValueParameter()->setType($type);
            $this->getGetValueMethod()->setReturnTypes([$type]);
        } else if ($type = TypeUtil::mapXsdTypeToInternalXsdType($restriction->getBase(), $this->xsdFactory)) {
            $this->getValueProperty()->setType($type);
            $this->getValueParameter()->setType($type);
            $this->getGetValueMethod()->setReturnTypes([$type]);
        }

        $enumerations = [];

        foreach ($restriction->getFacets() as $facet) {
            if ($facet instanceof Enumeration) {
                $const = 'VALUE_' . strtoupper($facet->getValue());
                $this->c->addConstant($const, Scalar::string($facet->getValue()));
                $enumerations[] = $const;

            } else if ($facet instanceof FractionDigits) {
                $this->constructor->getBody()
                    ->if(Cast::toInt(Variable::named('value'))->isNotIdenticalTo(Variable::named('value')))
                        ->execute(Variable::named('decimals')->equals(
                            ResultOf::strlen(Variable::named('value'))
                                ->minus(ResultOf::strpos(Variable::named('value'), Scalar::string('.')))
                                ->minus(Scalar::int(1)))
                        )
                    ->else()
                        ->execute(Variable::named('decimals')->equals(Scalar::int(0)))
                    ->done()
                    ->if(Scalar::int((int) $facet->getValue())->isNotIdenticalTo(Variable::named('value')))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value can only contain ' . $facet->getValue() . ' decimal digits')]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof Length) {
                $this->constructor->getBody()
                    ->if(Scalar::int((int) $facet->getValue())->isNotIdenticalTo(ResultOf::strlen(Variable::named('value'))))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value must be ' . $facet->getValue() . ' characters')]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof MaxExclusive) {
                $this->constructor->getBody()
                    ->if(Variable::named('value')->isGreaterThanOrEqualTo(Scalar::int((int) $facet->getValue())))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value out of bounds')]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof MaxInclusive) {
                $this->constructor->getBody()
                    ->if(Variable::named('value')->isGreaterThan(Scalar::int((int) $facet->getValue())))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value out of bounds')]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof MaxLength) {
                $this->constructor->getBody()
                    ->if(Variable::named('value')->isGreaterThan(Scalar::int((int) $facet->getValue())))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value must be less than ' . $facet->getValue() . ' characters')]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof MinExclusive) {
                $this->constructor->getBody()
                    ->if(Variable::named('value')->isLessThanOrEqualTo(Scalar::int((int) $facet->getValue())))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value out of bounds')]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof MinInclusive) {
                $this->constructor->getBody()
                    ->if(Variable::named('value')->isLessThan(Scalar::int((int) $facet->getValue())))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value out of bounds')]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof MinLength) {
                $this->constructor->getBody()
                    ->if(Scalar::int((int) $facet->getValue())->isGreaterThan(ResultOf::strlen(Variable::named('value'))))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value must be more than ' . $facet->getValue() . ' characters')]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof Pattern) {
                $pattern = str_replace(['[0-9]', '/'], ['\\d', '\\/'], $facet->getValue());
                $this->constructor->getBody()
                    ->if(Logic::not(ResultOf::preg_match(Scalar::string('/' . $pattern . '/'), Variable::named('value'))))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value does not match pattern ' . $pattern)]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof TotalDigits) {
                $this->constructor->getBody()
                    ->if(Scalar::int((int) $facet->getValue())->isNotIdenticalTo(ResultOf::preg_match_all(Scalar::string('/\d/', Variable::named('value')))))
                        ->throw(NewInstance::of($this->validationException, [Scalar::string('value must contain ' . $facet->getValue() . ' digits')]))
                    ->done()
                    ->newLine()
                ;

            } else if ($facet instanceof WhiteSpace) {
                // @todo handle white space
            }
        }

        if (!empty($enumerations)) {
            $this->constructor->getBody()
                ->if(Logic::not(ResultOf::in_array(Variable::named('value'), Type::array(array_map(function (string $const) {
                    return Reference::self()->constant($const);
                }, $enumerations)), Type::true())))
                    ->throw(NewInstance::of($this->validationException, [Scalar::string('Bad value for enumeration')]))
                ->done()
                ->newLine()
            ;
        }
    }

    /**
     * @return Property
     */
    private function getValueProperty(): Property
    {
        foreach ($this->c->getProperties() as $property) {
            if ($property->getName() === 'value') {
                return $property;
            }
        }
    }

    /**
     * @return Parameter
     */
    private function getValueParameter(): Parameter
    {
        foreach($this->c->getMethodByName('__construct')->getParameters() as $parameter) {
            if ($parameter->getName() === 'value') {
                return $parameter;
            }
        }
    }

    /**
     * @return Method
     */
    private function getGetValueMethod(): Method
    {
        foreach ($this->c->getMethods() as $method) {
            if ($method->getName() === 'getValue') {
                return $method;
            }
        }
    }
}
