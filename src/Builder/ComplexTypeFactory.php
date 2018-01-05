<?php
declare(strict_types=1);

namespace JDWil\Zest\Builder;

use Doctrine\Common\Inflector\Inflector;
use JDWil\PhpGenny\Builder\Node\Cast;
use JDWil\PhpGenny\Builder\Node\Logic;
use JDWil\PhpGenny\Builder\Node\NewInstance;
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
use JDWil\Zest\Element\All;
use JDWil\Zest\Element\Any;
use JDWil\Zest\Element\AnyAttribute;
use JDWil\Zest\Element\Attribute;
use JDWil\Zest\Element\AttributeGroup;
use JDWil\Zest\Element\Choice;
use JDWil\Zest\Element\ComplexContent;
use JDWil\Zest\Element\ComplexType;
use JDWil\Zest\Element\Element;
use JDWil\Zest\Element\Extension;
use JDWil\Zest\Element\Group;
use JDWil\Zest\Element\Sequence;
use JDWil\Zest\Element\SimpleContent;
use JDWil\Zest\Element\SimpleType;
use JDWil\Zest\Exception\InvalidSchemaException;
use JDWil\Zest\Util\NamespaceUtil;
use JDWil\Zest\Util\TypeUtil;
use JDWil\Zest\XsdType\NonNegativeInteger;
use JDWil\Zest\XsdType\QName;
use Symfony\Component\Console\Output\OutputInterface;

class ComplexTypeFactory extends AbstractTypeFactory
{
    /**
     * @var Class_[]
     */
    protected $classes;

    /**
     * @var int
     */
    protected $propertyCounter;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var SimpleTypeFactory
     */
    protected $simpleTypeFactory;

    /**
     * @var XsdTypeFactory
     */
    protected $xsdTypeFactory;

    /**
     * @var ElementFactory
     */
    protected $elementFactory;

    /**
     * @var ZestClassFactory
     */
    protected $zestClassFactory;

    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(
        Config $config,
        XsdTypeFactory $xsdTypeFactory,
        SimpleTypeFactory $simpleTypeFactory,
        ZestClassFactory $zestClassFactory,
        ElementFactory $elementFactory,
        OutputInterface $output
    ) {
        parent::__construct($output);

        $this->classes = [];
        $this->classStack = [];
        $this->config = $config;
        $this->simpleTypeFactory = $simpleTypeFactory;
        $this->xsdTypeFactory = $xsdTypeFactory;
        $this->elementFactory = $elementFactory;
        $this->zestClassFactory = $zestClassFactory;
    }

    /**
     * @param ComplexType $complexType
     * @return Class_
     * @throws InvalidSchemaException
     * @throws \Exception
     */
    public function buildComplexType(ComplexType $complexType): Class_
    {
        $this->propertyCounter = 1;

        $this->debug('buildComplexType called', $complexType->getName());

        $c = new Class_($complexType->getName());
        $c->setNamespace(NamespaceUtil::schemaToNamespace(
            $complexType->getSchemaNamespace(),
            $this->config->namespacePrefix
        ));

        $this->debug('FQN is ' . $c->getFqn(), $c->getName());

        $c->implements($this->zestClassFactory->buildStreamableInterface());
        if (isset($this->classes[$c->getFqn()])) {
            $this->debug('Class already created... returning that', $c->getName());

            return $this->classes[$c->getFqn()];
        }

        $this->classes[$c->getFqn()] = $c;

        $c->setAbstract($complexType->isAbstract());
        // @todo implement final properly
        $c->setFinal($complexType->getFinal() === '#all');
        $this->debug('Abstract: ' . (string) $c->isAbstract() . ' Final: ' . (string) $c->isFinal(), $c->getName());

        $this->processComplexType($complexType, $c);

        $this->pruneUnusuedValidationCalls($c);

        $this->debug('Done building class', $c->getName());

        return $c;
    }

    /**
     * @param ComplexType $complexType
     * @param Class_ $c
     * @throws InvalidSchemaException
     * @throws \Exception
     */
    public function processComplexType(ComplexType $complexType, Class_ $c)
    {
        $cn = $c->getName();
        $validate = $this->getValidateMethod($c);

        $this->debug('Adding call to validate in writeToStream method', $cn);

        $this->getWriteToStreamMethod($c)->getBody()
            ->execute(Variable::named('this')->call($validate))
            ->newLine()
            ->execute(
                Variable::named('stream')->call('write', Scalar::string('<')->concat(Variable::named('tag')))
            )
        ;

        $this->debug('Adding attributes', $cn);
        $this->addAttributes($complexType->getAttributes(), $c);

        $this->debug('Adding attribute groups', $cn);
        $this->addAttributeGroups($complexType->getAttributeGroups(), $c);

        if ($anyAttribute = $complexType->getAnyAttribute()) {
            $this->debug('Adding any attribute', $cn);
            $this->addAnyAttribute($anyAttribute, $c);
        }

        $ending = $complexType->hasXmlChildren() ? '>' : '/>';
        $this->debug('Ending tag: ' . $ending, $cn);
        $this->getWriteToStreamMethod($c)->getBody()->execute(
            Variable::named('stream')->call('writeLine', Scalar::string($ending))
        );

        if ($simpleContent = $complexType->getSimpleContent()) {
            $this->debug('Adding simple content', $cn);
            $this->addSimpleContent($simpleContent, $c);
        }

        if ($complexContent = $complexType->getComplexContent()) {
            $this->debug('Adding complex content', $cn);
            $this->addComplexContent($complexContent, $c);
        }

        if ($group = $complexType->getGroup()) {
            $this->debug('Adding group', $cn);
            $this->addGroup($group, $c);
        }

        if ($all = $complexType->getAll()) {
            $this->debug('Adding all', $cn);
            $this->addAll($all, $c);
        }

        if ($choice = $complexType->getChoice()) {
            $this->debug('Adding choice', $cn);
            $this->addChoice($choice, $c);
        }

        if ($sequence = $complexType->getSequence()) {
            $this->debug('Adding sequence', $cn);
            $this->addSequence($sequence, $c);
        }

        if ($complexType->hasXmlChildren()) {
            $this->debug('Element has xml children. Adding code to print child content', $cn);
            $this->getWriteToStreamMethod($c)->getBody()->execute(
                Variable::named('stream')->call(
                    'writeLine', Scalar::string('</')->concat(Variable::named('tag')->concat(Scalar::string('>')))
                )
            );
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
     * @param SimpleContent $simpleContent
     * @param Class_ $c
     * @throws \Exception
     */
    private function addSimpleContent(SimpleContent $simpleContent, Class_ $c)
    {
        $cn = $c->getName();

        if ($restriction = $simpleContent->getRestriction()) {
            $this->debug('Adding restriction', $cn);
            $this->simpleTypeFactory->addRestrictions(
                $restriction,
                $c
            );
        } else if ($extension = $simpleContent->getExtension()) {
            $this->debug('Adding extension', $cn);
            $this->addExtension($extension, $c);
        }

        $this->debug('Adding class property: _content', $cn);
        $property = new Property('_content', Visibility::isPrivate());
        $this->addGetter($c, $property);
        $this->addSetter($c, $property);
        $c->addProperty($property);

        $this->debug('Adding writeToStream code', $cn);
        $this->getWriteToStreamMethod($c)->getBody()
            ->if(Type::null()->isNotIdenticalTo(Variable::named('this')->property('_content')))
                ->execute(Variable::named('stream')->call('writeLine', Variable::named('this')->property('_content')))
            ->done()
        ;
    }

    /**
     * @param ComplexContent $complexContent
     * @param Class_ $c
     * @throws InvalidSchemaException
     * @throws \Exception
     */
    private function addComplexContent(ComplexContent $complexContent, Class_ $c)
    {
        $cn = $c->getName();

        if ($restriction = $complexContent->getRestriction()) {
            $this->debug('Adding restriction', $cn);
            $this->simpleTypeFactory->addRestrictions(
                $restriction,
                $c
            );
        } else if ($extension = $complexContent->getExtension()) {
            $this->debug('Adding extension', $cn);
            $this->addExtension($extension, $c);
        }

        $this->debug('Adding class property: _content', $cn);
        $property = new Property('_content', Visibility::isPrivate());
        $this->addGetter($c, $property);
        $this->addSetter($c, $property);
        $c->addProperty($property);

        $this->debug('Adding writeToStream code', $cn);
        $this->getWriteToStreamMethod($c)->getBody()
            ->if(Type::null()->isNotIdenticalTo(Variable::named('this')->property('_content')))
            ->execute(Variable::named('stream')->call('writeLine', Variable::named('this')->property('_content')))
            ->done()
        ;
    }

    /**
     * @param Extension $extension
     * @param Class_ $c
     * @return array
     * @throws InvalidSchemaException
     * @throws \Exception
     */
    private function addExtension(Extension $extension, Class_ $c): array
    {
        $cn = $c->getName();
        $elements = [];

        if ($group = $extension->getGroup()) {
            $this->debug('Adding group', $cn);
            $elements = $this->addGroup($group, $c);
        } else if ($all = $extension->getAll()) {
            $this->debug('Adding all', $cn);
            $elements = $this->addAll($all, $c);
        } else if ($choice = $extension->getChoice()) {
            $this->debug('Adding choice', $cn);
            $elements = $this->addChoice($choice, $c);
        } else if ($sequence = $extension->getSequence()) {
            $this->debug('Adding sequence', $cn);
            $elements = $this->addSequence($sequence, $c);
        }

        $this->addAttributes($extension->getAttributes(), $c);
        $this->addAttributeGroups($extension->getAttributeGroups(), $c);

        if ($anyAttribute = $extension->getAnyAttribute()) {
            $this->debug('Adding any attribute', $cn);
            $this->addAnyAttribute($anyAttribute, $c);
        }

        return $elements;
    }

    /**
     * @param AnyAttribute $anyAttribute
     * @param Class_ $c
     * @throws \Exception
     */
    private function addAnyAttribute(AnyAttribute $anyAttribute, Class_ $c)
    {
        $cn = $c->getName();

        $this->debug('Adding class property: extraAttributes. Visibility: private, type: [], default: []', $cn);
        $property = new Property('extraAttributes', Visibility::isPrivate(), InternalType::array(), Type::array());
        $c->addProperty($property);

        $this->debug('Adding class method: addAttribute(string $key, string $value)', $cn);
        $addAttribute = new Method('addAttribute');
        $addAttribute->addParameter(new Parameter('key', InternalType::string()));
        $addAttribute->addParameter(new Parameter('value', InternalType::string()));
        $addAttribute->getBody()->execute(
            Variable::named('this')->property('extraAttributes')->arrayIndex(Variable::named('key'))
                ->equals(Variable::named('value'))
        );

        if ($this->config->generateFluidSetters) {
            $this->debug('Making method fluid', $cn);
            $addAttribute->setReturnTypes([$c->getName()]);
            $addAttribute->getBody()
                ->newLine()
                ->return(Variable::named('this'));
        }
        $c->addMethod($addAttribute);

        $this->debug('Adding class method: getAttributes(): array', $cn);
        $getAttributes = new Method('getAttributes');
        $getAttributes->setReturnTypes([InternalType::array()]);
        $getAttributes->getBody()->return(Variable::named('this')->property('extraAttributes'));
        $c->addMethod($getAttributes);

        $this->debug('Updating writeToStream to print all extra attributes', $cn);
        $this->getWriteToStreamMethod($c)->getBody()
            ->foreach(Variable::named('this')->property('extraAttributes'), Variable::named('value'), Variable::named('key'))
                ->execute(
                    Variable::named('stream')->call(
                        'write',
                        Scalar::string(' ')->concat(
                            Variable::named('key'))
                                ->concat(Scalar::string('="'))->concat(Variable::named('value'))->concat(Scalar::string('"')
                        )
                    )
                )
            ->done()
        ;
    }

    /**
     * @param AttributeGroup[] $attributeGroups
     * @param Class_ $c
     * @throws InvalidSchemaException
     * @throws \Exception
     */
    private function addAttributeGroups(array $attributeGroups, Class_ $c)
    {
        $cn = $c->getName();

        foreach ($attributeGroups as $attributeGroup) {
            if ($ref = $attributeGroup->getRef()) {
                $this->debug('Resolving reference for attribute group: ' . (string) $ref, $cn);
                $attributeGroup = $attributeGroup->resolveQNameToElement($ref);
                $this->debug('Got ' . $attributeGroup->getName(), $cn);
                $this->debug('Adding attribute group', $cn);
                $this->addAttributeGroup($attributeGroup, $c);
            } else {
                $this->debug('Adding attribute group', $cn);
                $this->addAttributeGroup($attributeGroup, $c);
            }
        }
    }

    /**
     * @param AttributeGroup $attributeGroup
     * @param Class_ $c
     * @throws \Exception
     */
    private function addAttributeGroup(AttributeGroup $attributeGroup, Class_ $c)
    {
        $cn = $c->getName();

        foreach ($attributeGroup->getAttributes() as $attribute) {
            $this->debug('Adding attribute', $cn);
            $this->addAttribute($attribute, $c);
        }

        foreach ($attributeGroup->getAttributeGroups() as $ag) {
            $this->debug('Adding attribute group', $cn);
            $this->addAttributeGroup($ag, $c);
        }
    }

    /**
     * @param Attribute[] $attributes
     * @param Class_ $c
     * @throws \Exception
     */
    private function addAttributes(array $attributes, Class_ $c)
    {
        foreach ($attributes as $attribute) {
            $this->debug('Adding attribute', $c->getName());
            $this->addAttribute($attribute, $c);
        }
    }

    /**
     * @param Attribute $attribute
     * @param Class_ $c
     * @throws \Exception
     */
    private function addAttribute(Attribute $attribute, Class_ $c)
    {
        $cn = $c->getName();

        if ($ref = $attribute->getRef()) {
            $this->debug('Resolving reference for attribute: ' . (string) $ref, $cn);
            try {
                $attribute = $attribute->resolveQNameToElement($ref);
                $this->debug('Got ' . $attribute->getName(), $cn);
            } catch (InvalidSchemaException $e) {
                $this->debug('Failed to resolve attribute reference. Assuming standard type.', $cn);
            }
        }

        if (null === $attribute->getName()) {
            $this->debug('Attribute name is null... bailing out', $cn);
            return;
        }

        $type = $this->resolveElementType($attribute);
        $this->debug('Type: ' . (string) $type, $cn);
        $defaultValue = TypeUtil::convertTypeToScalar($attribute->getType(), $attribute->getDefault());
        $this->debug('Default value: ' . (string) $defaultValue, $cn);

        $this->debug('Adding class property: ' . $attribute->getName(), $cn);
        $property = new Property($attribute->getName(), Visibility::isPrivate(), $type, $defaultValue);
        $c->addProperty($property);
        $this->addGetter($c, $property);
        $this->addSetter($c, $property);

        $value = Variable::named('this')->property($property->getName());
        if (!$type instanceof InternalType || !$type->isString()) {
            $value = Cast::toString($value);
        }

        $callWriteToStream = Variable::named('stream')->call(
            'write',
            Scalar::string(' ' . $attribute->getName() . '="')->concat(
                $value
            )->concat(
                Scalar::string('"')
            )
        );

        if ($attribute->getUse() === 'required') {
            $this->debug('Adding write call in writeToStream method', $cn);
            $this->getWriteToStreamMethod($c)->getBody()->execute($callWriteToStream);
        } else if ($attribute->getUse() === 'optional') {
            $this->debug('Use is optional... adding write call inside if statement', $cn);
            $this->getWriteToStreamMethod($c)->getBody()
                ->if(Type::null()->isNotIdenticalTo($value))
                    ->execute($callWriteToStream)
                ->done()
            ;
        }
    }

    /**
     * @param Group $group
     * @param Class_ $c
     * @return array
     * @throws InvalidSchemaException
     * @throws \Exception
     */
    private function addGroup(Group $group, Class_ $c): array
    {
        $cn = $c->getName();

        if ($ref = $group->getRef()) {
            $this->debug('Resolving group reference: ' . (string) $ref, $cn);
            $group = $group->resolveQNameToElement($ref);
            $this->debug('Got ' . $group->getName(), $cn);
        }

        if ($sequence = $group->getSequence()) {
            $this->debug('Adding sequence', $cn);
            return $this->addSequence($sequence, $c);
        } else if ($choice = $group->getChoice()) {
            $this->debug('Adding choice', $cn);
            return $this->addChoice($choice, $c);
        } else if ($all = $group->getAll()) {
            $this->debug('Adding all', $cn);
            return $this->addAll($all, $c);
        }
    }

    /**
     * @param All $all
     * @param Class_ $c
     * @return array
     * @throws InvalidSchemaException
     * @throws \Exception
     */
    private function addAll(All $all, Class_ $c): array
    {
        $cn = $c->getName();
        $properties = [];
        foreach ($all->getElements() as $element) {
            $this->debug('Adding element', $cn);
            $properties[] = $this->addElement($element, $c);
        }

        // @todo do we need to validate min/max here again?
        return $properties;
    }

    /**
     * @param Any $any
     * @param Class_ $c
     * @return array
     * @throws \Exception
     */
    private function addAny(Any $any, Class_ $c): array
    {
        $cn = $c->getName();

        $this->debug('Adding class property: extraElements', $cn);
        $property = new Property(
            'extraElements',
            Visibility::isPrivate(),
            InternalType::arrayOf($this->zestClassFactory->buildStreamableInterface()),
            Type::array()
        );
        $c->addProperty($property);

        $this->debug('Adding class method: addElement(StreamableInterface $element', $cn);
        $m = new Method('addElement');
        $m->addParameter(new Parameter('element', $this->zestClassFactory->buildStreamableInterface()));
        $m->getBody()
            ->execute(
                Variable::named('this')->property('extraElements')->arrayIndex()->equals(Variable::named('element'))
            )
        ;

        if ($this->config->generateFluidSetters) {
            $this->debug('Making adder fluid', $cn);
            $m->setReturnTypes([$c->getName()]);
            $m->getBody()->newLine()->return(Variable::named('this'));
        }
        $c->addMethod($m);

        $this->debug('Adding class method: getElements(): array');
        $m = new Method('getElements');
        $m->setReturnTypes([InternalType::arrayOf($this->zestClassFactory->buildStreamableInterface())]);
        $m->getBody()->return(Variable::named('this')->property('extraElements'));
        $c->addMethod($m);

        $this->debug('Updating writeToStream method', $cn);
        $this->getWriteToStreamMethod($c)->getBody()
            ->foreach(Variable::named('this')->property('extraElements'), Variable::named('element'))
                ->execute(Variable::named('element')->call('writeToStream', Variable::named('stream')))
            ->done()
        ;

        $min = $any->getMinOccurs()->getValue();
        $max = $any->getMaxOccurs();

        if ($min > 0) {
            $this->debug('Min > 0... adding validation', $cn);
            $this->getValidateMethod($c)->getBody()
                ->if(Scalar::int($min)->isGreaterThan(ResultOf::count(Variable::named('this')->property('extraElements'))))
                    ->throw(NewInstance::of($this->zestClassFactory->buildValidationException(), [Scalar::string('Not enough extra elements')]))
                ->done()
            ;
        }

        if (false !== $max) {
            $this->debug('Max is bounded... adding validation', $cn);
            $this->getValidateMethod($c)->getBody()
                ->if(Scalar::int($max->getValue())->isLessThan(ResultOf::count(Variable::named('this')->property('extraElements'))))
                    ->throw(NewInstance::of($this->zestClassFactory->buildValidationException(), [Scalar::string('Too many extra elements')]))
                ->done()
            ;
        }

        return ['extraElements'];
    }

    /**
     * @param Choice $choice
     * @param Class_ $c
     * @return array
     * @throws InvalidSchemaException
     * @throws \Exception
     */
    private function addChoice(Choice $choice, Class_ $c): array
    {
        $elements = $arrays = $toCheck = [];
        foreach ($choice->getElements() as $element) {
            $e = $this->addElement($element, $c, $choice);
            $elements[] = $e;
            $toCheck[] = $e;
        }

        foreach ($choice->getGroups() as $group) {
            $a = $this->addGroup($group, $c);
            $arrays[] = $a;
            $toCheck[] = end($a);
        }

        foreach ($choice->getChoices() as $ch) {
            $a = $this->addChoice($ch, $c);
            $arrays[] = $a;
            $toCheck[] = end($a);
        }

        foreach ($choice->getSequences() as $sequence) {
            $a = $this->addSequence($sequence, $c);
            $arrays[] = $a;
            $toCheck[] = end($a);
        }

        foreach ($choice->getAny() as $any) {
            $a = $this->addAny($any, $c);
            $arrays[] = $a;
            $toCheck[] = end($a);
        }

        $validate = $this->getValidateMethod($c);
        $validate->getBody()->execute(Variable::named('count')->equals(Scalar::int(0)));
        /** @var Property $check */
        foreach ($toCheck as $check) {
            $condition = ((string) $check->getType()) === InternalType::ARRAY ?
                Logic::not(ResultOf::empty(Variable::named('this')->property($check->getName()))) :
                Type::null()->isNotIdenticalTo(Variable::named('this')->property($check->getName()))
            ;

            $validate->getBody()
                ->if($condition)
                    ->execute(Variable::named('count')->postIncrement())
                ->done()
            ;
        }

        // @todo build a better error message
        $validate->getBody()
            ->if(Variable::named('count')->isGreaterThan(Scalar::int(1)))
                ->throw(NewInstance::of($this->zestClassFactory->buildValidationException(), [Scalar::string('Only one element can be used in a choice')]))
            ->done()
        ;

        return array_merge($elements, ...$arrays);
    }

    /**
     * @param Sequence $sequence
     * @param Class_ $c
     * @return array
     * @throws InvalidSchemaException
     * @throws \Exception
     */
    private function addSequence(Sequence $sequence, Class_ $c): array
    {
        $arrays = $elements = [];

        foreach ($sequence->getChildElements() as $element) {
            if ($element instanceof Element) {
                $elements[] = $this->addElement($element, $c);
            } else if ($element instanceof Group) {
                $arrays[] = $this->addGroup($element, $c);
            } else if ($element instanceof Choice) {
                $arrays[] = $this->addChoice($element, $c);
            } else if ($element instanceof Sequence) {
                $arrays[] = $this->addSequence($element, $c);
            } else if ($element instanceof Any) {
                $arrays[] = $this->addAny($element, $c);
            }
        }

        return array_merge($elements, ...$arrays);
    }

    /**
     * @param Element $element
     * @param Class_ $c
     * @param AbstractElement|null $parent
     * @return Property The element property
     * @throws InvalidSchemaException
     * @throws \Exception
     */
    private function addElement(Element $element, Class_ $c, AbstractElement $parent = null): Property
    {
        $cn = $c->getName();

        if ($ref = $element->getRef()) {
            $this->debug('Resolving element reference: ' . (string) $ref, $cn);
            $parent = $element->resolveQNameToElement($ref);
            $this->debug('Got ' . $parent->getName(), $cn);
            $element = $this->mergeElements($parent, $element);
        }

        while ($substitutionGroup = $element->getSubstitutionGroup()) {
            $this->debug('Resolve element substitution group: ' . (string) $substitutionGroup, $cn);
            $parent = $element->resolveQNameToElement($substitutionGroup);
            $this->debug('Got ' . $parent->getName(), $cn);
            $element = $this->mergeElements($parent, $element);
        }

        $type = $this->resolveElementType($element);
        $this->debug('Element Type: ' . (string) $type, $cn);

        $xsdType = null;
        if (null !== $element->getType()) {
            try {
                $xsdType = $element->resolveQNameToElement($element->getType());
            } catch (InvalidSchemaException $e) {
                $xsdType = 'special';
            }
        }
        $this->debug('XSD Type: ' . (string) $xsdType, $cn);

        $propertyName = $element->getName() ?? 'property' . $this->propertyCounter++;
        $this->debug('Property name: ' . $propertyName, $cn);

        $defaultValue = TypeUtil::convertTypeToScalar($element->getType(), $element->getDefault());
        $this->debug('Default: ' . (string) $defaultValue, $cn);

        $min = $max = 0;
        $arrayType = false;
        $maxOccurs = $element->getMaxOccurs();
        if ($maxOccurs === false || ($maxOccurs instanceof NonNegativeInteger && $maxOccurs->getValue() > 1)) {
            $this->debug('Max occurs > 1', $cn);

            $max = false === $maxOccurs ? 0 : $maxOccurs->getValue();
            $arrayType = true;

            $this->debug('Adding class method: ' . 'add' . Inflector::classify($propertyName), $cn);
            $adderParam = new Parameter($propertyName, $type);
            $adder = new Method('add' . Inflector::classify($propertyName));

            $this->debug('Changing property name to ' . Inflector::pluralize($propertyName), $cn);
            $propertyName = Inflector::pluralize($propertyName);
            $type = InternalType::arrayOf($type);
            $defaultValue = Type::array();

            $adder->addParameter($adderParam);
            $adder->getBody()->execute(
                Variable::named('this')->property($propertyName)->arrayIndex()
                    ->equals(Variable::named($adderParam->getName()))
            );
            if ($this->config->generateFluidSetters) {
                $adder->setReturnTypes([$c->getName()]);
                $adder->getBody()
                    ->newLine()
                    ->return(Variable::named('this'))
                ;
            }
            $c->addMethod($adder);
        }

        $minOccurs = $element->getMinOccurs();
        if ($minOccurs instanceof NonNegativeInteger && $minOccurs->getValue() > 0) {
            $this->debug('Min occurs is > 0', $cn);
            $min = $minOccurs->getValue();
        }

        $this->debug('Adding class property: ' . $propertyName, $cn);
        $property = new Property($propertyName, Visibility::isPrivate(), $type, $defaultValue);
        if ($min === 0 && (!$type instanceof InternalType || !$type->isArray())) {
            $property->addType(InternalType::null());
        }
        $c->addProperty($property);
        $this->addGetter($c, $property);
        $this->addSetter($c, $property);

        $this->debug('Adding writeToStream code', $cn);
        $tag = null === $element->getName() ? Type::null() : Scalar::string($element->getName());
        $var = $arrayType ? Variable::named('p') : Variable::named('this')->property($propertyName);
        if (!$arrayType && (!$type instanceof InternalType || !$type->isString())) {
            $var = Cast::toString($var);
        } else if ($arrayType && (!$type->getArrayType() instanceof InternalType || !$type->getArrayType()->isString())) {
            $var = Cast::toString($var);
        }

        if ($xsdType instanceof SimpleType || 'special' === $xsdType) {
            $write = null === $element->getName() ?
                Variable::named('stream')->call('write', Cast::toString($var)) :
                Variable::named('stream')->call(
                    'writeLine',
                    Scalar::string('<')
                        ->concat($tag)
                        ->concat(Scalar::string('>'))
                        ->concat($var)
                        ->concat(Scalar::string('</'))
                        ->concat($tag)
                        ->concat(Scalar::string('>'))
                )
            ;

        } else {
            $methodToCall = $type instanceof Class_ ?
                $type->getMethodByName('writeToStream') :
                'writeToStream'
            ;

            $write = $arrayType ?
                Variable::named('p')->call($methodToCall, Variable::named('stream'), $tag) :
                Variable::named('this')->property($propertyName)->call($methodToCall, Variable::named('stream'), $tag)
            ;
        }

        if ($arrayType) {
            $this->getWriteToStreamMethod($c)->getBody()
                ->foreach(Variable::named('this')->property($propertyName), Variable::named('p'))
                    ->execute($write)
                ->done()
            ;
        } else {
            if (!$parent instanceof Choice && $min > 0) {
                $this->getWriteToStreamMethod($c)->getBody()->execute($write);
            } else {
                $this->getWriteToStreamMethod($c)->getBody()
                    ->if(Type::null()->isNotIdenticalTo(Variable::named('this')->property($propertyName)))
                        ->execute($write)
                    ->done()
                ;
            }
        }

        $this->debug('Adding validation code', $cn);

        if ($arrayType) {
            $validate = $this->getValidateMethod($c);

            if ($min > 0) {
                $this->debug('Min > 0... adding validation', $cn);
                // @todo build a better error message
                $validate->getBody()
                    ->if(ResultOf::count(Variable::named('this')->property($propertyName))->isLessThan(Scalar::int($min)))
                    ->throw(NewInstance::of($this->zestClassFactory->buildValidationException(), [Scalar::string('property is out of bounds')]))
                    ->done();
            }

            if ($max !== 0) {
                $this->debug('Max is bounded... adding validation', $cn);
                // @todo build a better error message
                $validate->getBody()
                    ->if(ResultOf::count(Variable::named('this')->property($propertyName))->isGreaterThan(Scalar::int($max)))
                    ->throw(NewInstance::of($this->zestClassFactory->buildValidationException(), [Scalar::string('property is out of bounds')]))
                    ->done();
            }
        }

        return $property;
    }

    /**
     * @param AbstractElement $element
     * @param QName|null $type
     * @return bool|Class_|InternalType|null
     * @throws InvalidSchemaException
     * @throws \Exception
     */
    public function resolveElementType(AbstractElement $element, QName $type = null)
    {
        if (null === $type && !$type = $element->getType()) {
            $this->debug('Element has no type property and none provided... returning false');

            return false;
        }

        if ($internalType = TypeUtil::mapXsdTypeToInternalType($type)) {
            return $internalType;
        } else if ($internalType = TypeUtil::mapXsdTypeToInternalXsdType($type, $this->xsdTypeFactory)) {
            return $internalType;
        } else if ($elementType = $element->resolveQNameToElement($type)) {
            if ($elementType instanceof SimpleType) {
                return $this->simpleTypeFactory->buildSimpleType($elementType);
            } else if ($elementType instanceof ComplexType) {
                return $this->buildComplexType($elementType);
            }
        }

        throw new InvalidSchemaException('Invalid element type attribute', $element);
    }

    /**
     * @param Class_ $c
     * @return Method
     */
    private function getConstructor(Class_ $c)
    {
        return $this->getMethod($c, '__construct');
    }

    /**
     * @param Class_ $c
     * @return Method
     */
    private function getValidateMethod(Class_ $c): Method
    {
        return $this->getMethod($c, 'validate');
    }

    /**
     * @param Class_ $c
     * @param string $name
     * @return Method
     */
    private function getMethod(Class_ $c, string $name): Method
    {
        if ($m = $c->getMethodByName($name)) {
            return $m;
        }

        $m = new Method($name);
        $c->addMethod($m);

        return $m;
    }

    /**
     * @param Class_ $c
     * @return Method
     */
    private function getWriteToStreamMethod(Class_ $c): Method
    {
        return $c->getMethodByName('writeToStream');
    }

    /**
     * @param Class_ $c
     */
    private function pruneUnusuedValidationCalls(Class_ $c)
    {
        $validate = $this->getValidateMethod($c);
        if (empty($validate->getBody()->getNodes())) {
            $c->removeMethod($validate);
            $write = $this->getWriteToStreamMethod($c);
            $nodes = $write->getBody()->getNodes();
            array_splice($nodes, 0, 2);
            $write->getBody()->setNodes($nodes);
        }
    }

    /**
     * @param Class_ $c
     * @param Property $property
     */
    private function addGetter(Class_ $c, Property $property)
    {
        $m = new Method('get' . Inflector::classify($property->getName()));
        $m->setReturnTypes($property->getTypes());
        $m->getBody()->return(Variable::named('this')->property($property->getName()));
        $c->addMethod($m);
    }

    /**
     * @param Class_ $c
     * @param Property $property
     */
    private function addSetter(Class_ $c, Property $property)
    {
        $m = new Method('set' . Inflector::classify($property->getName()));
        $m->addParameter(new Parameter($property->getName(), $property->getType()));
        $m->getBody()->execute(
            Variable::named('this')->property($property->getName())->equals(Variable::named($property->getName()))
        );

        if ($this->config->generateFluidSetters) {
            $m->setReturnTypes([$c->getName()]);
            $m->getBody()->newLine()->return(Variable::named('this'));
        }

        $c->addMethod($m);
    }

    /**
     * @param Element $e1
     * @param Element $e2
     * @return Element
     */
    private function mergeElements(Element $e1, Element $e2)
    {
        $ret = clone $e1;

        if (null !== $e2->getMinOccurs()) {
            $ret->setMinOccurs($e2->getMinOccurs());
        }

        if (null !== $e2->getMaxOccurs()) {
            $ret->setMaxOccurs($e2->getMaxOccurs());
        }

        if (null !== $e2->getName()) {
            $ret->setName($e2->getName());
        }

        return $ret;
    }
}
