<?php
declare(strict_types=1);

namespace JDWil\Zest\Builder;

use JDWil\PhpGenny\Builder\Node\Type;
use JDWil\PhpGenny\Type\Class_;
use JDWil\PhpGenny\Type\Interface_;
use JDWil\PhpGenny\Type\Method;
use JDWil\PhpGenny\Type\Parameter;
use JDWil\PhpGenny\ValueObject\InternalType;

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

    public function buildCollection(): Class_
    {

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
