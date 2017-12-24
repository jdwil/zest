<?php
declare(strict_types=1);

namespace JDWil\Zest\XsdType;

/**
 * Class QName
 */
class QName
{
    /**
     * @var string|null
     */
    private $namespace;

    /**
     * @var string
     */
    private $name;

    /**
     * QName constructor.
     * @param string $qname
     */
    public function __construct(string $qname)
    {
        if (strpos($qname, ':') !== false) {
            list($ns, $name) = explode(':', $qname);
            $this->namespace = $ns;
            $this->name = $name;
        } else {
            $this->name = $qname;
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return null === $this->namespace ?
            $this->name :
            $this->namespace . ':' . $this->name;
    }

    /**
     * @return null|string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
