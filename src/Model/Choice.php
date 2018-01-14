<?php
declare(strict_types=1);

namespace JDWil\Zest\Model;

use JDWil\ExcelStream\Excel\Zest\OutputStreamInterface;
use JDWil\ExcelStream\Excel\Zest\StreamableInterface;

/**
 * Class Choice
 */
class Choice implements StreamableInterface
{
    /**
     * @var array
     */
    protected $allowedTypes;

    /**
     * @var mixed
     */
    protected $item;

    /**
     * Choice constructor.
     * @param array $allowedTypes
     * @param mixed $item
     */
    public function __construct(array $allowedTypes, $item = null)
    {
        $this->allowedTypes = $allowedTypes;
        if (null !== $item) {
            $this->item = $item;
            $this->validate();
        }
    }

    public function __clone()
    {
        $this->item = null;
    }

    /**
     * @param mixed $item
     */
    public function add($item)
    {
        $this->item = $item;
        $this->validate();
    }

    /**
     * @return bool
     */
    public function selected(): bool
    {
        return null !== $this->item;
    }

    /**
     * @param string $type
     * @return bool
     */
    public function allowsType(string $type): bool
    {
        return \in_array($type, $this->allowedTypes, true);
    }

    /**
     * @return mixed|null
     */
    public function get()
    {
        return $this->item;
    }

    public function validate()
    {
        if (!\in_array(\get_class($this->item), $this->allowedTypes, true)) {
            throw new \InvalidArgumentException('Type must be one of ' . implode(', ', $this->allowedTypes));
        }
    }

    /**
     * @param OutputStreamInterface $stream
     * @param string|null $tag
     * @param bool $rootElement
     */
    public function writeToStream(OutputStreamInterface $stream, string $tag = null, bool $rootElement = false)
    {
        if ($this->item instanceof StreamableInterface) {
            $name = \array_search(\get_class($this->item), $this->allowedTypes, true);
            $this->item->writeToStream($stream, $name);
        }
    }
}
