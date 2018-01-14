<?php
declare(strict_types=1);

namespace JDWil\Zest\Builder;

use JDWil\PhpGenny\Builder\Node\Logic;
use JDWil\PhpGenny\Builder\Node\NewInstance;
use JDWil\PhpGenny\Builder\Node\Reference;
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
use JDWil\Unify\TestRunner\TestPlanInterface;

class ZestClassFactory
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Class_[]
     */
    protected $classes;

    /**
     * @var Interface_[]
     */
    protected $interfaces;

    /**
     * ZestClassFactory constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->classes = [];
        $this->interfaces = [];
    }

    /**
     * @return Interface_
     * @throws \Exception
     */
    public function buildStreamableInterface(): Interface_
    {
        if (isset($this->interfaces['StreamableInterface'])) {
            return $this->interfaces['StreamableInterface'];
        }

        $i = new Interface_('StreamableInterface');
        $i->setNamespace($this->config->getZestClassNamespace());

        $writeToStream = new Method('writeToStream');
        $writeToStream->addParameter(new Parameter('stream', $this->buildOutputStreamInterface()));
        $writeToStream->addParameter(new Parameter('tag', InternalType::string(), Type::null()));
        $writeToStream->addParameter(new Parameter('rootElement', InternalType::bool(), Type::false()));
        $i->addMethod($writeToStream);

        $this->interfaces['StreamableInterface'] = $i;

        return $i;
    }

    /**
     * @return Interface_
     * @throws \Exception
     */
    public function buildOutputStreamInterface(): Interface_
    {
        if (isset($this->interfaces['OutputStreamInterface'])) {
            return $this->interfaces['OutputStreamInterface'];
        }

        $i = new Interface_('OutputStreamInterface');
        $i->setNamespace($this->config->getZestClassNamespace());

        $write = new Method('write');
        $write->addParameter(new Parameter('data', InternalType::string()));
        $i->addMethod($write);

        $writeLine = new Method('writeLine');
        $writeLine->addParameter(new Parameter('data', InternalType::string()));
        $i->addMethod($writeLine);

        $this->interfaces['OutputStreamInterface'] = $i;

        return $i;
    }

    /**
     * @return Class_
     */
    public function buildValidationException(): Class_
    {
        if (isset($this->classes['validationException'])) {
            return $this->classes['validationException'];
        }

        $c = new Class_('ValidationException');
        $c->setNamespace($this->config->getZestClassNamespace() . '\\Exception');
        $c->setExtends('\\Exception');

        $this->classes['validationException'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildChoice(): Class_
    {
        if (isset($this->classes['choice'])) {
            return $this->classes['choice'];
        }

        $c = new Class_('Choice');
        $c->setNamespace($this->config->getZestClassNamespace());
        $c->implements($this->buildStreamableInterface());

        $c->addProperty(new Property('allowedTypes', Visibility::isProtected(), InternalType::array()));
        $c->addProperty(new Property('item', Visibility::isProtected(), InternalType::mixed()));

        $m = new Method('__construct');
        $m->addParameter(new Parameter('allowedTypes', InternalType::array()));
        $m->addParameter(new Parameter('item', InternalType::mixed(), Type::null()));
        $m->getBody()
            ->execute(Variable::named('this')->property('allowedTypes')->equals(Variable::named('allowedTypes')))
            ->if(Type::null()->isNotIdenticalTo(Variable::named('item')))
                ->execute(Variable::named('this')->property('item')->equals(Variable::named('item')))
                ->execute(Variable::named('this')->call('validate'))
            ->done()
        ;
        $c->addMethod($m);

        $m = new Method('__clone');
        $m->getBody()->execute(Variable::named('this')->property('item')->equals(Type::null()));
        $c->addMethod($m);

        $m = new Method('add');
        $m->addParameter(new Parameter('item', InternalType::mixed()));
        $m->getBody()
            ->execute(Variable::named('this')->property('item')->equals(Variable::named('item')))
            ->execute(Variable::named('this')->call('validate'))
        ;
        $c->addMethod($m);

        $m = new Method('selected');
        $m->setReturnTypes([InternalType::bool()]);
        $m->getBody()->return(Type::null()->isNotIdenticalTo(Variable::named('this')->property('item')));
        $c->addMethod($m);

        $m = new Method('allowsType');
        $m->addParameter(new Parameter('type', InternalType::string()));
        $m->setReturnTypes([InternalType::bool()]);
        $m->getBody()->return(ResultOf::in_array(Variable::named('type'), Variable::named('this')->property('allowedTypes'), Type::true()));
        $c->addMethod($m);

        $m = new Method('get');
        $m->getBody()->return(Variable::named('this')->property('item'));
        $c->addMethod($m);

        $m = new Method('validate');
        $m->getBody()
            ->if(Logic::not(ResultOf::in_array(ResultOf::get_class(Variable::named('this')->property('item')), Variable::named('this')->property('allowedTypes'), Type::true())))
                ->throw(NewInstance::of('\InvalidArgumentException', [Scalar::string('Type must be one of ')->concat(ResultOf::implode(Scalar::string(', '), Variable::named('this')->property('allowedTypes')))]))
            ->done()
        ;
        $c->addMethod($m);

        $c->getMethodByName('writeToStream')->getBody()
            ->if(Variable::named('this')->property('item')->instanceOf($this->buildStreamableInterface()))
                ->execute(Variable::named('name')->equals(ResultOf::array_search(ResultOf::get_class(Variable::named('this')->property('item')), Variable::named('this')->property('allowedTypes'), Type::true())))
                ->execute(Variable::named('this')->property('item')->call('writeToStream', Variable::named('stream'), Variable::named('name')))
            ->done()
        ;

        $this->classes['choice'] = $c;

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildCollection(): Class_
    {
        if (isset($this->classes['collection'])) {
            return $this->classes['collection'];
        }

        $c = new Class_('Collection');
        $c->setNamespace($this->config->getZestClassNamespace());
        $c->implements($this->buildStreamableInterface());

        $this->classes['collection'] = $c;

        $c->addProperty(new Property('type', Visibility::isPrivate(), [
            InternalType::string(),
            'Choice',
            'Sequence'
        ]));
        $c->addProperty(new Property('items', Visibility::isPrivate(), InternalType::array()));
        $c->addProperty(new Property('minSize', Visibility::isPrivate(), InternalType::int()));
        $c->addProperty(new Property('maxSize', Visibility::isPrivate(), InternalType::int()));

        $m = new Method('__construct');
        $m->addParameter(new Parameter('type'));
        $m->addParameter(new Parameter('minSize', InternalType::int()));
        $m->addParameter(new Parameter('maxSize', InternalType::int()));
        $m->getBody()
            ->execute(Variable::named('this')->property('items')->equals(Type::array()))
            ->execute(Variable::named('this')->property('minSize')->equals(Variable::named('minSize')))
            ->execute(Variable::named('this')->property('maxSize')->equals(Variable::named('maxSize')))
            ->execute(Variable::named('this')->property('type')->equals(Variable::named('type')))
        ;
        $c->addMethod($m);

        $m = new Method('all');
        $m->setReturnTypes([InternalType::array()]);
        $m->getBody()->return(Variable::named('this')->property('items'));
        $c->addMethod($m);

        $m = new Method('add');
        $m->addParameter(new Parameter('item'));
        $m->getBody()
            ->execute(Variable::named('type')->equals(ResultOf::get_class(Variable::named('item'))))
            ->newLine()
            ->if(ResultOf::is_string(Variable::named('this')->call('getType')))
                ->if(Variable::named('this')->call('getType')->isIdenticalTo(Variable::named('type'))->booleanAnd(
                    Variable::named('this')->call('hasRoom')
                ))
                    ->execute(Variable::named('this')->property('items')->arrayIndex()->equals(Variable::named('item')))
                    ->return()
                ->done()
            ->else()
                ->if(ResultOf::get_class(Variable::named('this')->call('getType'))->isIdenticalTo(Variable::named('type'))->booleanAnd(
                    Variable::named('this')->call('hasRoom')
                ))
                    ->execute(Variable::named('this')->property('items')->arrayIndex()->equals(Variable::named('item')))
                    ->return()
                ->done()
            ->done()
            ->newLine()
            ->if(Variable::named('container')->equals(Variable::named('this')->call('findEmptySlot', Variable::named('this')->property('items'), Variable::named('type'), Variable::named('this'))))
                ->execute(Variable::named('container')->call('add', Variable::named('item')))
                ->return()
            ->done()
            ->newLine()
            ->throw(NewInstance::of('\InvalidArgumentException', [Scalar::string('No place to put item')]))
        ;
        $c->addMethod($m);

        $m = new Method('remove');
        $m->addParameter(new Parameter('item'));
        $m->addParameter(new Parameter('strict', InternalType::bool(), Type::false()));
        $m->setReturnTypes([InternalType::int()]);
        $m->getBody()
            ->execute(Variable::named('this')->call('throwIfNotValid', Variable::named('item')))
            ->newLine()
            ->execute(Variable::named('removed')->equals(Scalar::int(0)))
            ->while(Variable::named('key')->equals(ResultOf::array_search(Variable::named('item'), Variable::named('this')->property('items'), Variable::named('strict'))))
                ->execute(ResultOf::array_splice(Variable::named('this')->property('items'), Variable::named('key'), Scalar::int(1)))
                ->execute(Variable::named('removed')->postIncrement())
            ->done()
            ->newLine()
            ->return(Variable::named('removed'))
        ;
        $c->addMethod($m);

        $m = new Method('contains');
        $m->addParameter(new Parameter('item'));
        $m->addParameter(new Parameter('strict', InternalType::bool(), Type::true()));
        $m->setReturnTypes([InternalType::bool()]);
        $m->getBody()
            ->execute(Variable::named('this')->call('throwIfNotValid', Variable::named('item')))
            ->newLine()
            ->return(ResultOf::in_array(Variable::named('item'), Variable::named('this')->property('items'), Variable::named('strict')))
        ;
        $c->addMethod($m);

        $m = new Method('count');
        $m->setReturnTypes([InternalType::int()]);
        $m->getBody()->return(ResultOf::count(Variable::named('this')->property('items')));
        $c->addMethod($m);

        $m = new Method('isValid');
        $m->setReturnTypes([InternalType::bool()]);
        $m->getBody()
            ->foreach(Variable::named('this')->property('items'), Variable::named('item'))
            ->if(Variable::named('item')->instanceOf(Reference::self())->booleanAnd(
                Logic::not(Variable::named('item')->call('isValid'))
            ))
            ->return(Type::false())
            ->done()
            ->done()
            ->newLine()
            ->return(
                ResultOf::count(Variable::named('this')->property('items'))->isGreaterThanOrEqualTo(
                    Variable::named('this')->property('minSize')
                )->booleanAnd(
                    Scalar::int(-1)->isIdenticalTo(Variable::named('this')->property('maxSize'))->booleanOr(
                        ResultOf::count(Variable::named('this')->property('items'))->isLessThanOrEqualTo(
                            Variable::named('this')->property('maxSize')
                        )
                    )
                )
            )
        ;
        $c->addMethod($m);

        $m = new Method('getType');
        $m->setReturnTypes([InternalType::mixed()]);
        $m->getBody()->return(Variable::named('this')->property('type'));
        $c->addMethod($m);

        $m = new Method('hasRoom');
        $m->setReturnTypes([InternalType::bool()]);
        $m->getBody()
            ->return(Scalar::int(-1)->isIdenticalTo(Variable::named('this')->property('maxSize'))->booleanOr(
                Variable::named('this')->call('count')->isLessThan(Variable::named('this')->property('maxSize'))
            ))
        ;
        $c->addMethod($m);

        $m = new Method('throwIfNotValid', Visibility::isPrivate());
        $m->addParameter(new Parameter('item'));
        $m->getBody()
            ->execute(Variable::named('e')->equals(NewInstance::of('\InvalidArgumentException', [Scalar::string('Item must be of type ')->concat(Variable::named('this')->property('type'))])))
            ->if(ResultOf::is_string(Variable::named('this')->property('type'))->booleanAnd(Logic::not(Variable::named('item')->instanceOf(Variable::named('this')->property('type')))))
                ->throw(Variable::named('e'))
            ->elseIf(ResultOf::get_class(Variable::named('this')->property('type'))->isNotIdenticalTo(ResultOf::get_class(Variable::named('item'))))
                ->throw(Variable::named('e'))
            ->done()
        ;
        $c->addMethod($m);

        $c->getMethodByName('writeToStream')->getBody()
            ->foreach(Variable::named('this')->property('items'), Variable::named('item'))
                ->if(Variable::named('item')->instanceOf($this->buildStreamableInterface()))
                    ->execute(Variable::named('item')->call('writeToStream', Variable::named('stream')))
                ->done()
            ->done()
        ;

        $m = new Method('findEmptySlot', Visibility::isPrivate());
        $m->addParameter(new Parameter('items', InternalType::array()));
        $m->addParameter(new Parameter('type', InternalType::string()));
        $m->addParameter(new Parameter('parent'));
        $m->getBody()
            ->if(Variable::named('parent')->instanceOf(Reference::self())
                ->booleanAnd(
                    Logic::not(ResultOf::is_string(Variable::named('parent')->call('getType')))
                )->booleanAnd(
                    Variable::named('parent')->call('hasRoom')
                )->booleanAnd(
                    Variable::named('parent')->call('getType')->call('allowsType', Variable::named('type'))
                )
            )
                ->execute(Variable::named('ret')->equals(Variable::named('parent')->call('getType')->clone()))
                ->execute(Variable::named('parent')->call('add', Variable::named('ret')))
                ->newLine()
                ->return(Variable::named('ret'))
            ->done()
            ->newLine()
            ->foreach(Variable::named('items'), Variable::named('item'))
                ->if(Variable::named('item')->instanceOf(Reference::self()))
                    ->if(Variable::named('item')->call('getType')->isIdenticalTo(Variable::named('type'))->booleanAnd(Variable::named('item')->call('hasRoom')))
                        ->return(Variable::named('item'))
                    ->done()
                    ->newLine()
                    ->if(Variable::named('ret')->equals(Variable::named('this')->call('findEmptySlot', Variable::named('item')->call('all'), Variable::named('type'), Variable::named('item'))))
                        ->return(Variable::named('ret'))
                    ->done()
                ->elseIf(Variable::named('item')->instanceOf($this->buildChoice()))
                    ->if(Variable::named('item')->call('allowsType', Variable::named('type')))
                        ->if(Logic::not(Variable::named('item')->call('selected')))
                            ->return(Variable::named('item'))
                        ->done()
                        ->newLine()
                        ->if(Variable::named('parent')->instanceOf(Reference::self())->booleanAnd(Variable::named('parent')->call('hasRoom')))
                            ->execute(Variable::named('item')->equals(Variable::named('item')->clone()))
                            ->execute(Variable::named('parent')->call('add', Variable::named('item')))
                            ->newLine()
                            ->return(Variable::named('item'))
                        ->done()
                    ->done()
                    ->newLine()
                    ->if(Variable::named('ret')->equals(Variable::named('this')->call('findEmptySlot', Type::array([Variable::named('item')->call('get')]), Variable::named('type'), Variable::named('item'))))
                        ->return(Variable::named('ret'))
                    ->done()
                ->elseIf(Variable::named('item')->instanceOf($this->buildSequence()))
                    ->if(Variable::named('item')->call('allowsType', Variable::named('type')))
                        ->if(Logic::not(Variable::named('item')->call('getItemByType', Variable::named('type'))))
                            ->return(Variable::named('item'))
                        ->done()
                        ->newLine()
                        ->if(Variable::named('parent')->instanceOf(Reference::self())->booleanAnd(Variable::named('parent')->call('hasRoom')))
                            ->execute(Variable::named('item')->equals(Variable::named('item')->clone()))
                            ->execute(Variable::named('parent')->call('add', Variable::named('item')))
                            ->newLine()
                            ->return(Variable::named('item'))
                        ->done()
                    ->done()
                    ->if(Variable::named('item')->call('allowsType', Variable::named('type'))->booleanAnd(Logic::not(Variable::named('item')->call('getItemByType', Variable::named('type')))))
                        ->return(Variable::named('item'))
                    ->done()
                    ->newLine()
                    ->if(Variable::named('ret')->equals(Variable::named('this')->call('findEmptySlot', Type::array([Variable::named('item')->call('all')]), Variable::named('type'), Variable::named('item'))))
                        ->return(Variable::named('ret'))
                    ->done()
                ->done()
            ->done()
            ->newLine()
            ->return(Type::false())
        ;
        $c->addMethod($m);

        return $c;
    }

    /**
     * @return Class_
     * @throws \Exception
     */
    public function buildSequence(): Class_
    {
        if (isset($this->classes['sequence'])) {
            return $this->classes['sequence'];
        }

        $c = new Class_('Sequence');
        $c->setNamespace($this->config->getZestClassNamespace());
        $c->implements($this->buildStreamableInterface());

        $this->classes['sequence'] = $c;

        $c->addProperty(new Property('allowedTypes', Visibility::isProtected(), InternalType::array()));
        $c->addProperty(new Property('nameMap', Visibility::isProtected(), InternalType::array()));

        $m = new Method('__construct');
        $m->addParameter(new Parameter('minSize', InternalType::int(), Scalar::int(1)));
        $m->addParameter(new Parameter('maxSize', InternalType::int(), Scalar::int(1)));
        $m->getBody()
            ->execute(Reference::parent()->staticCall('__construct', Variable::named('minSize'), Variable::named('maxSize')))
            ->newLine()
            ->execute(Variable::named('this')->property('allowedTypes')->equals(Type::array()))
            ->execute(Variable::named('this')->property('nameMap')->equals(Type::array()))
            ->execute(Variable::named('this')->property('name')->equals(Scalar::string('_sequence')))
        ;
        $c->addMethod($m);

        $m = new Method('add');
        $m->addParameter(new Parameter('item'));
        $m->getBody()
            ->execute(Variable::named('this')->call('throwIfNotValid', Variable::named('item')))
            ->newLine()
            ->if(Variable::named('item')->instanceOf(Reference::class($this->buildCollection())))
                ->execute(Variable::named('this')->property('items')->arrayIndex(Variable::named('item')->call('getType'))->arrayIndex()->equals(Variable::named('item')))
            ->else()
                ->execute(Variable::named('this')->property('items')->arrayIndex(ResultOf::get_class(Variable::named('item')))->arrayIndex()->equals(Variable::named('item')))
            ->done()
        ;
        $c->addMethod($m);

        $m = new Method('addAllowedType');
        $m->addParameter(new Parameter('type', InternalType::string()));
        $m->addParameter(new Parameter('name', InternalType::string()));
        $m->getBody()
            ->if(ResultOf::in_array(Variable::named('type'), Variable::named('this')->property('allowedTypes'), Type::true()))
                ->execute(Variable::named('this')->property('allowedTypes')->arrayIndex()->equals(Variable::named('type')))
                ->execute(Variable::named('this')->property('nameMap')->arrayIndex(Variable::named('type'))->equals(Variable::named('name')))
                ->execute(Variable::named('this')->property('items')->arrayIndex(Variable::named('type'))->equals(Type::array()))
            ->done();
        $c->addMethod($m);

        $m = new Method('throwIfNotValid', Visibility::isPrivate());
        $m->addParameter(new Parameter('item'));
        $m->getBody()
            ->if(Variable::named('item')->instanceOf(Reference::class($this->buildCollection())))
                ->return()
            ->done()
            ->newLine()
            ->execute(Variable::named('valid')->equals(Type::false()))
            ->foreach(Variable::named('this')->property('allowedTypes'), Variable::named('type'))
                ->if(Variable::named('item')->instanceOf(Variable::named('type')))
                    ->execute(Variable::named('valid')->equals(Type::true()))
                    ->break()
                ->done()
            ->done()
            ->newLine()
            ->if(Logic::not(Variable::named('valid')))
                ->throw(NewInstance::of('\InvalidArgumentException', [Scalar::string('Wrong type for collection of ')->concat(ResultOf::implode(Scalar::string(', '), Variable::named('this')->property('allowedTypes')))]))
            ->done()
        ;
        $c->addMethod($m);

        $itemAtIndex = Variable::named('this')->property('items')->arrayIndex(Variable::named('type'))->arrayIndex(Variable::named('index'));
        $c->getMethodByName('writeToStream')->getBody()
            ->execute(Variable::named('index')->equals(Scalar::int(0)))
            ->execute(Variable::named('stop')->equals(Type::false()))
            ->while(Logic::not(Variable::named('stop')))
                ->execute(Variable::named('stop')->equals(Type::true()))
                ->foreach(Variable::named('this')->property('allowedTypes'), Variable::named('type'))
                    ->if(ResultOf::isset($itemAtIndex))
                        ->execute(Variable::named('item')->equals($itemAtIndex))
                    ->elseIf(Variable::named('item')->instanceOf(Reference::class($this->buildCollection())))
                        ->foreach(Variable::named('item')->call('all'), Variable::named('collectionItem'))
                            ->if(Variable::named('collectionItem')->instanceOf(Reference::class($this->buildStreamableInterface())))
                                ->execute(Variable::named('collectionItem')->call('writeToStream', Variable::named('stream'), Variable::named('this')->property('nameMap')->arrayIndex(Variable::named('type'))))
                            ->done()
                        ->done()
                    ->done()
                    ->execute(Variable::named('stop')->equals(Type::false()))
                ->done()
                ->newLine()
                ->execute(Variable::named('index')->postIncrement())
            ->done()
        ;

        return $c;
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
}
