<?php
declare(strict_types=1);

namespace JDWil\Zest\Builder;

use JDWil\PhpGenny\Builder\Node\Cast;
use JDWil\PhpGenny\Builder\Node\Logic;
use JDWil\PhpGenny\Builder\Node\NewInstance;
use JDWil\PhpGenny\Builder\Node\ResultOf;
use JDWil\PhpGenny\Builder\Node\Scalar;
use JDWil\PhpGenny\Builder\Node\Type;
use JDWil\PhpGenny\Builder\Node\Variable;
use JDWil\PhpGenny\Type\Class_;
use JDWil\PhpGenny\Type\Interface_;
use JDWil\PhpGenny\Type\Method;
use JDWil\PhpGenny\Type\Parameter;
use JDWil\PhpGenny\Type\Property;
use JDWil\PhpGenny\ValueObject\InternalType;
use JDWil\PhpGenny\ValueObject\Visibility;

class XsdTypeFactory
{
    /**
     * @var Class_[]
     */
    protected $classes;

    /**
     * @var Interface_[]
     */
    protected $interfaces;

    /**
     * @var Method[]
     */
    protected $methods;

    /**
     * @var Property[]
     */
    protected $properties;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var Class_
     */
    protected $exceptionClass;

    /**
     * XsdTypeFactory constructor.
     * @param Config $config
     * @param ZestClassFactory $zestClassFactory
     */
    public function __construct(Config $config, ZestClassFactory $zestClassFactory)
    {
        $this->classes = [];
        $this->interfaces = [];
        $this->methods = [];
        $this->properties = [];
        $this->namespace = $config->getXsdClassNamespace();
        $this->exceptionClass = $zestClassFactory->buildValidationException();
    }

    /**
     * @return Class_[]
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * @return Interface_[]
     */
    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildAnyUri(): Class_
    {
        if ($c = $this->getClass('anyUri')) {
            return $c;
        }

        $c = new Class_('AnyUri');
        $c->setNamespace($this->namespace);
        $c->setExtends($this->buildAbstractStringType());
        $c->addMethod($this->buildToStringMethod());
        $c->addMethod($this->buildThrowNotValidMethod('value must be a valid URI'));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::string()));
        $constructor->getBody()
            ->if(Logic::not(Variable::named('this')->call('isValidUri', Variable::named('value'))))
                ->execute(Variable::named('this')->call('throwNotValid'))
            ->done()
            ->newLine()
            ->execute(Variable::named('this')->property('value')->equals(Variable::named('value')))
        ;
        $c->addMethod($constructor);

        $isValidUri = new Method('isValidUri', Visibility::isPrivate());
        $isValidUri->addParameter(new Parameter('uri', InternalType::string()));
        $isValidUri->setReturnTypes([InternalType::bool()]);
        $isValidUri
            ->getBody()
            ->return(
                Cast::toBool(
                    ResultOf::preg_match(
                        Scalar::string('#(([a-zA-Z][0-9a-zA-Z+\-\.]*:)?/{0,2}[0-9a-zA-Z;/?:@\&=+$\.\-!~*\'()%]+)?(\#[0-9a-zA-Z;/?:@\&=+$\.\-_!~*\'()%]+)?#'),
                        Variable::named('uri')
                    )
                )
            )
        ;
        $c->addMethod($isValidUri);

        $this->classes['anyUri'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildBase64Binary(): Class_
    {
        if ($c = $this->getClass('base64Binary')) {
            return $c;
        }

        $c = new Class_('Base64Binary');
        $c->setNamespace($this->namespace);
        $c->setExtends($this->buildAbstractStringType());
        $c->addMethod($this->buildToStringMethod());

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::string()));
        $constructor->getBody()
            ->if(Logic::not(Variable::named('this')->call('isBase64', Variable::named('value'))))
                ->execute(Variable::named('this')->call('throwNotValid'))
            ->done()
            ->newLine()
            ->execute(Variable::named('this')->property('value')->equals(Variable::named('value')))
        ;
        $c->addMethod($constructor);

        $isBase64 = new Method('isBase64', Visibility::isPrivate());
        $isBase64->setReturnTypes([InternalType::bool()]);
        $isBase64->addParameter(new Parameter('string', InternalType::string()));
        $isBase64
            ->getBody()
            ->return(
                Cast::toBool(
                    ResultOf::preg_match(Scalar::string('%^[a-zA-Z0-9/+]*={0,2}$%'), Variable::named('string'))
                )
            )
        ;
        $c->addMethod($isBase64);

        $c->addMethod($this->buildThrowNotValidMethod('Value is not base64 encoded'));

        $this->classes['base64Binary'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildByte(): Class_
    {
        if ($c = $this->getClass('byte')) {
            return $c;
        }

        $c = new Class_('Byte');
        $c->setNamespace($this->namespace);
        $c->setExtends($this->buildAbstractIntegerType());
        $c->addMethod($this->buildToStringMethod());
        $c->addMethod($this->buildThrowNotValidMethod('byte is out of range'));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::int()));
        $constructor->getBody()
            ->if(Logic::not(Variable::named('this')->call('isInRange', Variable::named('value'))))
                ->execute(Variable::named('this')->call('throwNotValid'))
            ->done()
            ->newLine()
            ->execute(Variable::named('this')->property('value')->equals(Variable::named('value')))
        ;
        $c->addMethod($constructor);

        $isInRange = new Method('isInRange');
        $isInRange->addParameter(new Parameter('number', InternalType::int()));
        $isInRange->getBody()->return(
            Variable::named('number')->isGreaterThanOrEqualTo(Scalar::int(-128))
                ->booleanAnd(
                    Variable::named('number')->isLessThanOrEqualTo(Scalar::int(127))
                )
        );
        $c->addMethod($isInRange);

        $this->classes['byte'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildDate(): Class_
    {
        if ($c = $this->getClass('date')) {
            return $c;
        }

        $c = new Class_('Date');
        $c->setNamespace($this->namespace);
        $c->addProperty(new Property('value', Visibility::isProtected(), '\\DateTime'));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::string()));
        $constructor->getBody()
            ->execute(
                Variable::named('this')->property('value')->equals(NewInstance::of('\\DateTime', [Variable::named('value')]))
            )
        ;
        $c->addMethod($constructor);

        $toString = new Method('__toString');
        $toString->setReturnTypes([InternalType::string()]);
        $toString->getBody()->return(Variable::named('this')->property('value')->call('format', Scalar::string('Y-m-dP')));
        $c->addMethod($toString);

        $getValue = new Method('getValue');
        $getValue->setReturnTypes(['\\DateTime']);
        $getValue->getBody()->return(Variable::named('this')->property('value'));
        $c->addMethod($getValue);

        $this->classes['date'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildDateTime(): Class_
    {
        if ($c = $this->getClass('dateTime')) {
            return $c;
        }

        $c = new Class_('DateTime');
        $c->setNamespace($this->namespace);
        $c->setExtends($this->buildDate());

        $toString = new Method('__toString');
        $toString->setReturnTypes([InternalType::string()]);
        $toString->getBody()->return(Variable::named('this')->property('value')->call('format', Scalar::string('Y-m-d\\TH:i:sP')));
        $c->addMethod($toString);

        $this->classes['dateTime'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildDuration(): Class_
    {
        if ($c = $this->getClass('duration')) {
            return $c;
        }

        $c = new Class_('Duration');
        $c->setNamespace($this->namespace);
        $c->addProperty(new Property('value', Visibility::isProtected(), '\\DateInterval'));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::string()));
        $constructor->getBody()->execute(
            Variable::named('this')->property('value')->equals(NewInstance::of('\\DateInterval', [Variable::named('value')]))
        );
        $c->addMethod($constructor);

        $toString = new Method('__toString');
        $toString->setReturnTypes([InternalType::string()]);
        $toString->getBody()->return(
            Variable::named('this')->property('value')->call('format', Scalar::string('P%yY%mM%dDT%hH%iM%sS'))
        );
        $c->addMethod($toString);

        $getValue = new Method('getValue');
        $getValue->setReturnTypes(['\\DateInterval']);
        $getValue->getBody()->return(Variable::named('this')->property('value'));
        $c->addMethod($getValue);

        $this->classes['duration'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildGDay(): Class_
    {
        if ($c = $this->getClass('gday')) {
            return $c;
        }

        $c = new Class_('GDay');
        $c->setNamespace($this->namespace);
        $c->addProperty(new Property('value', Visibility::isProtected(), InternalType::string()));
        $c->addMethod($this->buildToStringMethod());
        $c->addMethod($this->buildThrowNotValidMethod('value must be in format "---dd'));
        $c->addMethod($this->buildGetValueMethod(InternalType::string()));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::string()));
        $constructor
            ->getBody()
            ->execute(
                Variable::named('this')->property('value')->equals(
                    Cast::toString(
                        ResultOf::str_replace(Scalar::string(' '), Scalar::string(''), Variable::named('value'))
                    )
                )
            )
            ->if(Logic::not(Variable::named('this')->call('isValidGDay', Variable::named('this')->property('value'))))
                ->execute(Variable::named('this')->call('throwNotValid'))
            ->done()
        ;
        $c->addMethod($constructor);

        $isValidGDay = new Method('isValidGDay', Visibility::isPrivate());
        $isValidGDay->setReturnTypes([InternalType::bool()]);
        $isValidGDay->addParameter(new Parameter('value', InternalType::string()));
        $isValidGDay->getBody()->return(
            Cast::toBool(
                ResultOf::preg_match(Scalar::string('/\-\-\-\d{1,2}Z?[\+-]?(\d{2}:\d{2})?/'), Variable::named('value'))
            )
        );
        $c->addMethod($isValidGDay);

        $this->classes['gday'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildGMonth(): Class_
    {
        if ($c = $this->getClass('gmonth')) {
            return $c;
        }

        $c = new Class_('GMonth');
        $c->setNamespace($this->namespace);
        $c->addProperty($this->buildValueProperty(InternalType::string()));
        $c->addMethod($this->buildToStringMethod());
        $c->addMethod($this->buildGetValueMethod(InternalType::string()));
        $c->addMethod($this->buildThrowNotValidMethod('value must be in format "--mm"'));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::string()));
        $constructor->getBody()
            ->execute(
                Variable::named('this')->property('value')->equals(
                    Cast::toString(
                        ResultOf::str_replace(Scalar::string(' '), Scalar::string(''), Variable::named('value'))
                    )
                )
            )
            ->if(Logic::not(Variable::named('this')->call('isValidGMonth', Variable::named('this')->property('value'))))
                ->execute(Variable::named('this')->call('throwNotValid'))
            ->done()
        ;
        $c->addMethod($constructor);

        $isValidGMonth = new Method('isValidGMonth', Visibility::isPrivate());
        $isValidGMonth->addParameter(new Parameter('value', InternalType::string()));
        $isValidGMonth->setReturnTypes([InternalType::bool()]);
        $isValidGMonth->getBody()->return(
            Cast::toBool(
                ResultOf::preg_match(Scalar::string('/\-\-\d{1,2}Z?[\+-]?(\d{2}:\d{2})?/'), Variable::named('value'))
            )
        );
        $c->addMethod($isValidGMonth);

        $this->classes['gmonth'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildGMonthDay(): Class_
    {
        if ($c = $this->getClass('gmonthday')) {
            return $c;
        }

        $c = new Class_('GMonthDay');
        $c->setNamespace($this->namespace);
        $c->addProperty($this->buildValueProperty(InternalType::string()));
        $c->addMethod($this->buildToStringMethod());
        $c->addMethod($this->buildGetValueMethod(InternalType::string()));
        $c->addMethod($this->buildThrowNotValidMethod('value must be in format "--mm-dd"'));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::string()));
        $constructor->getBody()
            ->execute(
                Variable::named('this')->property('value')->equals(
                    Cast::toString(
                        ResultOf::str_replace(Scalar::string(' '), Scalar::string(''), Variable::named('value'))
                    )
                )
            )
            ->if(Logic::not(Variable::named('this')->call('isValidGMonthDay', Variable::named('this')->property('value'))))
            ->execute(Variable::named('this')->call('throwNotValid'))
            ->done()
        ;
        $c->addMethod($constructor);

        $isValidGMonthDay = new Method('isValidGMonthDay', Visibility::isPrivate());
        $isValidGMonthDay->addParameter(new Parameter('value', InternalType::string()));
        $isValidGMonthDay->setReturnTypes([InternalType::bool()]);
        $isValidGMonthDay->getBody()->return(
            Cast::toBool(
                ResultOf::preg_match(Scalar::string('/\-\-\d{1,2}\-\d{2}Z?[\+-]?(\d{2}:\d{2})?/'), Variable::named('value'))
            )
        );
        $c->addMethod($isValidGMonthDay);

        $this->classes['gmonthday'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildGYear(): Class_
    {
        if ($c = $this->getClass('gyear')) {
            return $c;
        }

        $c = new Class_('GYear');
        $c->setNamespace($this->namespace);
        $c->addProperty($this->buildValueProperty(InternalType::string()));
        $c->addMethod($this->buildToStringMethod());
        $c->addMethod($this->buildGetValueMethod(InternalType::string()));
        $c->addMethod($this->buildThrowNotValidMethod('value must be in format "YYYY"'));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::string()));
        $constructor->getBody()
            ->execute(
                Variable::named('this')->property('value')->equals(
                    Cast::toString(
                        ResultOf::str_replace(Scalar::string(' '), Scalar::string(''), Variable::named('value'))
                    )
                )
            )
            ->if(Logic::not(Variable::named('this')->call('isValidGYear', Variable::named('this')->property('value'))))
            ->execute(Variable::named('this')->call('throwNotValid'))
            ->done()
        ;
        $c->addMethod($constructor);

        $isValidGYear = new Method('isValidGYear', Visibility::isPrivate());
        $isValidGYear->addParameter(new Parameter('value', InternalType::string()));
        $isValidGYear->setReturnTypes([InternalType::bool()]);
        $isValidGYear->getBody()->return(
            Cast::toBool(
                ResultOf::preg_match(Scalar::string('/\d{4}Z?[\+-]?(\d{2}:\d{2})?/'), Variable::named('value'))
            )
        );
        $c->addMethod($isValidGYear);

        $this->classes['gyear'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildGYearMonth(): Class_
    {
        if ($c = $this->getClass('gyearmonth')) {
            return $c;
        }

        $c = new Class_('GYearMonth');
        $c->setNamespace($this->namespace);
        $c->addProperty($this->buildValueProperty(InternalType::string()));
        $c->addMethod($this->buildToStringMethod());
        $c->addMethod($this->buildGetValueMethod(InternalType::string()));
        $c->addMethod($this->buildThrowNotValidMethod('value must be in format "YYYY-mm"'));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::string()));
        $constructor->getBody()
            ->execute(
                Variable::named('this')->property('value')->equals(
                    Cast::toString(
                        ResultOf::str_replace(Scalar::string(' '), Scalar::string(''), Variable::named('value'))
                    )
                )
            )
            ->if(Logic::not(Variable::named('this')->call('isValidGYearMonth', Variable::named('this')->property('value'))))
            ->execute(Variable::named('this')->call('throwNotValid'))
            ->done()
        ;
        $c->addMethod($constructor);

        $isValidGYearMonth = new Method('isValidGYearMonth', Visibility::isPrivate());
        $isValidGYearMonth->addParameter(new Parameter('value', InternalType::string()));
        $isValidGYearMonth->setReturnTypes([InternalType::bool()]);
        $isValidGYearMonth->getBody()->return(
            Cast::toBool(
                ResultOf::preg_match(Scalar::string('/\d{4}\-\d{2}Z?[\+-]?(\d{2}:\d{2})?/'), Variable::named('value'))
            )
        );
        $c->addMethod($isValidGYearMonth);

        $this->classes['gyearmonth'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildHexBinary(): Class_
    {
        if ($c = $this->getClass('hexbinary')) {
            return $c;
        }

        $c = new Class_('HexBinary');
        $c->setNamespace($this->namespace);
        $c->setExtends($this->buildAbstractStringType());
        $c->addMethod($this->buildToStringMethod());

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::string()));
        $constructor->getBody()->execute(Variable::named('this')->property('value')->equals(Variable::named('value')));
        $c->addMethod($constructor);

        $this->classes['hexbinary'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildLanguage(): Class_
    {
        if ($c = $this->getClass('language')) {
            return $c;
        }

        $c = new Class_('Language');
        $c->setNamespace($this->namespace);
        $c->setExtends($this->buildAbstractStringType());
        $c->addMethod($this->buildToStringMethod());
        $c->addMethod($this->buildThrowNotValidMethod('value is not a valid language'));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::string()));
        $constructor->getBody()
            ->if(Logic::not(Variable::named('this')->call('isValidLanguage', Variable::named('value'))))
                ->execute(Variable::named('this')->call('throwNotValid'))
            ->done()
            ->newLine()
            ->execute(Variable::named('this')->property('value')->equals(Variable::named('value')))
        ;
        $c->addMethod($constructor);

        $isValidLanguage = new Method('isValidLanguage', Visibility::isPrivate());
        $isValidLanguage->addParameter(new Parameter('value', InternalType::string()));
        $isValidLanguage->getBody()
            ->return(
                Cast::toBool(
                    ResultOf::preg_match(Scalar::string('/([a-zA-Z]{2}|[iI]-[a-zA-Z]+|[xX]-[a-zA-Z]{1,8})(-[a-zA-Z]{1,8})*/'), Variable::named('value'))
                )
            )
        ;
        $c->addMethod($isValidLanguage);

        $this->classes['language'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildNegativeInteger(): Class_
    {
        if ($c = $this->getClass('negativeInteger')) {
            return $c;
        }

        $c = new Class_('NegativeInteger');
        $c->setNamespace($this->namespace);
        $c->setExtends($this->buildAbstractIntegerType());
        $c->addMethod($this->buildToStringMethod());
        $c->addMethod($this->buildThrowNotValidMethod('value must be negative'));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::int()));
        $constructor->getBody()
            ->if(Logic::not(Variable::named('this')->call('isNegativeInteger', Variable::named('value'))))
                ->execute(Variable::named('this')->call('throwNotValid'))
            ->done()
            ->newLine()
            ->execute(Variable::named('this')->property('value')->equals(Variable::named('value')))
        ;
        $c->addMethod($constructor);

        $isNegativeInteger = new Method('isNegativeInteger', Visibility::isPrivate());
        $isNegativeInteger->addParameter(new Parameter('value', InternalType::int()));
        $isNegativeInteger->getBody()->return(Variable::named('value')->isLessThan(Scalar::int(0)));
        $c->addMethod($isNegativeInteger);

        $this->classes['negativeInteger'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildNonNegativeInteger(): Class_
    {
        if ($c = $this->getClass('nonNegativeInteger')) {
            return $c;
        }

        $c = new Class_('NonNegativeInteger');
        $c->setNamespace($this->namespace);
        $c->setExtends($this->buildAbstractIntegerType());
        $c->addMethod($this->buildToStringMethod());
        $c->addMethod($this->buildThrowNotValidMethod('value must be >= 0'));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::int()));
        $constructor->getBody()
            ->if(Logic::not(Variable::named('this')->call('isNonNegativeInteger', Variable::named('value'))))
            ->execute(Variable::named('this')->call('throwNotValid'))
            ->done()
            ->newLine()
            ->execute(Variable::named('this')->property('value')->equals(Variable::named('value')))
        ;
        $c->addMethod($constructor);

        $isNonNegativeInteger = new Method('isNonNegativeInteger', Visibility::isPrivate());
        $isNonNegativeInteger->addParameter(new Parameter('value', InternalType::int()));
        $isNonNegativeInteger->getBody()->return(Variable::named('value')->isGreaterThanOrEqualTo(Scalar::int(0)));
        $c->addMethod($isNonNegativeInteger);

        $this->classes['nonNegativeInteger'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildNonPositiveInteger(): Class_
    {
        if ($c = $this->getClass('nonPositiveInteger')) {
            return $c;
        }

        $c = new Class_('NonPositiveInteger');
        $c->setNamespace($this->namespace);
        $c->setExtends($this->buildAbstractIntegerType());
        $c->addMethod($this->buildToStringMethod());
        $c->addMethod($this->buildThrowNotValidMethod('value must be <= 0'));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::int()));
        $constructor->getBody()
            ->if(Logic::not(Variable::named('this')->call('isNonPositiveInteger', Variable::named('value'))))
            ->execute(Variable::named('this')->call('throwNotValid'))
            ->done()
            ->newLine()
            ->execute(Variable::named('this')->property('value')->equals(Variable::named('value')))
        ;
        $c->addMethod($constructor);

        $isNonPositiveInteger = new Method('isNonPositiveInteger', Visibility::isPrivate());
        $isNonPositiveInteger->addParameter(new Parameter('value', InternalType::int()));
        $isNonPositiveInteger->getBody()->return(Variable::named('value')->isLessThanOrEqualTo(Scalar::int(0)));
        $c->addMethod($isNonPositiveInteger);

        $this->classes['nonPositiveInteger'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildNormalizedString(): Class_
    {
        if ($c = $this->getClass('normalizedString')) {
            return $c;
        }

        $c = new Class_('NormalizedString');
        $c->setNamespace($this->namespace);
        $c->setExtends($this->buildAbstractStringType());
        $c->addMethod($this->buildToStringMethod());

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::string()));
        $constructor->getBody()->execute(
            Variable::named('this')->property('value')->equals(
                Cast::toString(
                    ResultOf::preg_replace(Scalar::string('/([\n\r\t])|(\s{2,})/'), Scalar::string(' '), Variable::named('value'))
                )
            )
        );
        $c->addMethod($constructor);

        $this->classes['normalizedString'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildPositiveInteger(): Class_
    {
        if ($c = $this->getClass('positiveInteger')) {
            return $c;
        }

        $c = new Class_('PositiveInteger');
        $c->setNamespace($this->namespace);
        $c->setExtends($this->buildAbstractIntegerType());
        $c->addMethod($this->buildToStringMethod());
        $c->addMethod($this->buildThrowNotValidMethod('value must be > 0'));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::int()));
        $constructor->getBody()
            ->if(Logic::not(Variable::named('this')->call('isPositiveInteger', Variable::named('value'))))
            ->execute(Variable::named('this')->call('throwNotValid'))
            ->done()
            ->newLine()
            ->execute(Variable::named('this')->property('value')->equals(Variable::named('value')))
        ;
        $c->addMethod($constructor);

        $isPositiveInteger = new Method('isPositiveInteger', Visibility::isPrivate());
        $isPositiveInteger->addParameter(new Parameter('value', InternalType::int()));
        $isPositiveInteger->getBody()->return(Variable::named('value')->isGreaterThan(Scalar::int(0)));
        $c->addMethod($isPositiveInteger);

        $this->classes['positiveInteger'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildShort(): Class_
    {
        if ($c = $this->getClass('short')) {
            return $c;
        }

        $c = new Class_('Short');
        $c->setNamespace($this->namespace);
        $c->setExtends($this->buildAbstractIntegerType());
        $c->addMethod($this->buildToStringMethod());
        $c->addMethod($this->buildThrowNotValidMethod('value is out of range'));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::int()));
        $constructor->getBody()
            ->if(Logic::not(Variable::named('this')->call('isInRange', Variable::named('value'))))
            ->execute(Variable::named('this')->call('throwNotValid'))
            ->done()
            ->newLine()
            ->execute(Variable::named('this')->property('value')->equals(Variable::named('value')))
        ;
        $c->addMethod($constructor);

        $isInRange = new Method('isInRange', Visibility::isPrivate());
        $isInRange->addParameter(new Parameter('value', InternalType::int()));
        $isInRange->getBody()->return(
            Variable::named('value')->isGreaterThanOrEqualTo(Scalar::int(-32768))
                ->booleanAnd(Variable::named('value')->isLessThanOrEqualTo(Scalar::int(32767)))
        );
        $c->addMethod($isInRange);

        $this->classes['short'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildTime(): Class_
    {
        if ($c = $this->getClass('time')) {
            return $c;
        }

        $c = new Class_('Time');
        $c->setNamespace($this->namespace);
        $c->setExtends($this->buildDate());

        $toString = new Method('__toString');
        $toString->setReturnTypes([InternalType::string()]);
        $toString->getBody()->return(Variable::named('this')->property('value')->call('format', Scalar::string('H:i:sP')));
        $c->addMethod($toString);

        $this->classes['time'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildToken(): Class_
    {
        if ($c = $this->getClass('token')) {
            return $c;
        }

        $c = new Class_('Token');
        $c->setNamespace($this->namespace);
        $c->setExtends($this->buildAbstractStringType());
        $c->addMethod($this->buildToStringMethod());

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::string()));
        $constructor->getBody()->execute(
            Variable::named('this')->property('value')->equals(
                Cast::toString(
                    ResultOf::trim(
                        ResultOf::preg_replace(Scalar::string('/([\n\r\t])|(\s{2,})/'), Scalar::string(' '), Variable::named('value'))
                    )
                )
            )
        );
        $c->addMethod($constructor);

        $this->classes['token'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildUnsignedByte(): Class_
    {
        if ($c = $this->getClass('unsignedByte')) {
            return $c;
        }

        $c = new Class_('UnsignedByte');
        $c->setNamespace($this->namespace);
        $c->setExtends($this->buildAbstractIntegerType());
        $c->addMethod($this->buildToStringMethod());
        $c->addMethod($this->buildThrowNotValidMethod('ubyte is out of range'));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::int()));
        $constructor->getBody()
            ->if(Logic::not(Variable::named('this')->call('isInRange', Variable::named('value'))))
            ->execute(Variable::named('this')->call('throwNotValid'))
            ->done()
            ->newLine()
            ->execute(Variable::named('this')->property('value')->equals(Variable::named('value')))
        ;
        $c->addMethod($constructor);

        $isInRange = new Method('isInRange', Visibility::isPrivate());
        $isInRange->addParameter(new Parameter('number', InternalType::int()));
        $isInRange->getBody()->return(
            Variable::named('number')->isLessThanOrEqualTo(Scalar::int(255))
                ->booleanAnd(Variable::named('number')->isGreaterThanOrEqualTo(Scalar::int(0)))
        );
        $c->addMethod($isInRange);

        $this->classes['unsignedByte'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildUnsignedInt(): Class_
    {
        if ($c = $this->getClass('unsignedInt')) {
            return $c;
        }

        $c = new Class_('UnsignedInt');
        $c->setNamespace($this->namespace);
        $c->setExtends($this->buildAbstractIntegerType());
        $c->addMethod($this->buildToStringMethod());
        $c->addMethod($this->buildThrowNotValidMethod('uInt is out of range'));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::int()));
        $constructor->getBody()
            ->if(Logic::not(Variable::named('this')->call('isInRange', Variable::named('value'))))
            ->execute(Variable::named('this')->call('throwNotValid'))
            ->done()
            ->newLine()
            ->execute(Variable::named('this')->property('value')->equals(Variable::named('value')))
        ;
        $c->addMethod($constructor);

        $isInRange = new Method('isInRange', Visibility::isPrivate());
        $isInRange->addParameter(new Parameter('number', InternalType::int()));
        $isInRange->getBody()->return(
            Variable::named('number')->isLessThanOrEqualTo(Scalar::int(4294967295))
                ->booleanAnd(Variable::named('number')->isGreaterThanOrEqualTo(Scalar::int(0)))
        );
        $c->addMethod($isInRange);

        $this->classes['unsignedInt'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildUnsignedLong(): Class_
    {
        if ($c = $this->getClass('unsignedLong')) {
            return $c;
        }

        $c = new Class_('UnsignedLong');
        $c->setNamespace($this->namespace);
        $c->setExtends($this->buildAbstractIntegerType());
        $c->addMethod($this->buildToStringMethod());
        $c->addMethod($this->buildThrowNotValidMethod('uLong is out of range'));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::int()));
        $constructor->getBody()
            ->if(Logic::not(Variable::named('this')->call('isInRange', Variable::named('value'))))
            ->execute(Variable::named('this')->call('throwNotValid'))
            ->done()
            ->newLine()
            ->execute(Variable::named('this')->property('value')->equals(Variable::named('value')))
        ;
        $c->addMethod($constructor);

        $isInRange = new Method('isInRange', Visibility::isPrivate());
        $isInRange->addParameter(new Parameter('number', InternalType::int()));
        $isInRange->getBody()->return(
            Variable::named('number')->isLessThanOrEqualTo(Scalar::float(18446744073709551615))
                ->booleanAnd(Variable::named('number')->isGreaterThanOrEqualTo(Scalar::int(0)))
        );
        $c->addMethod($isInRange);

        $this->classes['unsignedLong'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildUnsignedShort(): Class_
    {
        if ($c = $this->getClass('unsignedShort')) {
            return $c;
        }

        $c = new Class_('UnsignedShort');
        $c->setNamespace($this->namespace);
        $c->setExtends($this->buildAbstractIntegerType());
        $c->addMethod($this->buildToStringMethod());
        $c->addMethod($this->buildThrowNotValidMethod('uShort is out of range'));

        $constructor = new Method('__construct');
        $constructor->addParameter(new Parameter('value', InternalType::int()));
        $constructor->getBody()
            ->if(Logic::not(Variable::named('this')->call('isInRange', Variable::named('value'))))
            ->execute(Variable::named('this')->call('throwNotValid'))
            ->done()
            ->newLine()
            ->execute(Variable::named('this')->property('value')->equals(Variable::named('value')))
        ;
        $c->addMethod($constructor);

        $isInRange = new Method('isInRange', Visibility::isPrivate());
        $isInRange->addParameter(new Parameter('number', InternalType::int()));
        $isInRange->getBody()->return(
            Variable::named('number')->isLessThanOrEqualTo(Scalar::int(65535))
                ->booleanAnd(Variable::named('number')->isGreaterThanOrEqualTo(Scalar::int(0)))
        );
        $c->addMethod($isInRange);

        $this->classes['unsignedShort'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    protected function buildAbstractIntegerType(): Class_
    {
        if (isset($this->classes['abstractIntegerType'])) {
            return $this->classes['abstractIntegerType'];
        }

        $c = new Class_('AbstractIntegerType');
        $c->setNamespace($this->namespace);
        $c->setAbstract(true);
        $c->implements($this->buildIntegerTypeInterface());
        $c->addProperty($this->buildValueProperty(InternalType::int()));
        $c->addMethod($this->buildGetValueMethod(InternalType::int()));

        $this->classes['abstractIntegerType'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    protected function buildAbstractStringType(): Class_
    {
        if (isset($this->classes['abstractStringType'])) {
            return $this->classes['abstractStringType'];
        }

        $c = new Class_('AbstractStringType');
        $c->setNamespace($this->namespace);
        $c->setAbstract(true);
        $c->implements($this->buildStringTypeInterface());
        $c->addProperty($this->buildValueProperty(InternalType::string()));
        $c->addMethod($this->buildGetValueMethod(InternalType::string()));

        $this->classes['abstractStringType'] = $c;

        return $c;
    }

    /**
     * @return Interface_
     * @throws \Exception
     */
    protected function buildIntegerTypeInterface(): Interface_
    {
        return $this->buildInternalTypeInterface('IntegerTypeInterface', InternalType::int());
    }

    /**
     * @return Interface_
     * @throws \Exception
     */
    protected function buildStringTypeInterface(): Interface_
    {
        return $this->buildInternalTypeInterface('StringTypeInterface', InternalType::string());
    }

    /**
     * @return Method
     */
    protected function buildToStringInterfaceMethod(): Method
    {
        if (isset($this->methods['toStringInterface'])) {
            return $this->methods['toStringInterface'];
        }

        $m = new Method(
            '__toString',
            Visibility::isPublic(),
            $final = false,
            $abstract = false,
            $parameters = [],
            $returnTypes = [
                InternalType::string()
            ]
        );

        $this->methods['toStringInterface'] = $m;

        return $m;
    }

    /**
     * @return Method
     */
    protected function buildToStringMethod(): Method
    {
        if ($m = $this->getMethod('toString')) {
            return $m;
        }

        $m = new Method('__toString');
        $m->setReturnTypes([InternalType::string()]);
        $m->getBody()->return(Cast::toString(Variable::named('this')->property('value')));

        $this->methods['toString'] = $m;

        return $m;
    }

    /**
     * @param InternalType $type
     * @return Method
     */
    protected function buildGetValueMethod(InternalType $type): Method
    {
        $key = 'getValue-' . (string) $type;
        if (isset($this->methods[$key])) {
            return $this->methods[$key];
        }

        $m = new Method('getValue');
        $m->setReturnTypes([$type]);
        $m->getBody()->return(Variable::named('this')->property('value'));

        $this->methods[$key] = $m;

        return $m;
    }

    /**
     * @param string $message
     * @return Method
     */
    protected function buildThrowNotValidMethod(string $message): Method
    {
        $m = new Method('throwNotValid');
        $m->setVisibility(Visibility::isPrivate());
        $m->getBody()->throw(NewInstance::of($this->exceptionClass, [Scalar::string($message)]));

        return $m;
    }

    /**
     * @param InternalType $type
     * @return Property
     */
    protected function buildValueProperty(InternalType $type): Property
    {
        $key = 'value-' . (string) $type;
        if (isset($this->properties[$key])) {
            return $this->properties[$key];
        }

        $p = new Property('value');
        $p->setType($type);

        $this->properties[$key] = $p;

        return $p;
    }

    /**
     * @param string $name
     * @param InternalType $type
     * @return Interface_
     * @throws \Exception
     */
    private function buildInternalTypeInterface(string $name, InternalType $type): Interface_
    {
        $key = 'typeInterface-' . (string) $type;
        if (isset($this->interfaces[$key])) {
            return $this->interfaces[$key];
        }

        $i = new  Interface_($name);
        $i->setNamespace($this->namespace);

        $i->addMethod(
            new Method(
                '__construct',
                null,
                false,
                false,
                [
                    new Parameter('value', $type)
                ]
            )
        );

        $i->addMethod($this->buildToStringInterfaceMethod());
        $i->addMethod($this->buildGetValueMethod($type));

        $this->interfaces[$key] = $i;

        return $i;
    }

    /**
     * @param string $key
     * @return false|Class_
     */
    private function getClass(string $key)
    {
        return $this->classes[$key] ?? false;
    }

    /**
     * @param string $key
     * @return false|Method
     */
    private function getMethod(string $key)
    {
        return $this->methods[$key] ?? false;
    }
}
