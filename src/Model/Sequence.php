<?php
declare(strict_types=1);

namespace JDWil\Zest\Model;

use JDWil\ExcelStream\Excel\Zest\OutputStreamInterface;
use JDWil\ExcelStream\Excel\Zest\StreamableInterface;

/**
 * Class Sequence
 */
class Sequence implements StreamableInterface
{
    /**
     * @var array
     */
    protected $allowedTypes;

    /**
     * @var array
     */
    protected $items;

    /**
     * Sequence constructor.
     * @param array $allowedTypes
     */
    public function __construct(array $allowedTypes)
    {
        $this->items = [];
        $this->allowedTypes = $allowedTypes;
    }

    public function __clone()
    {
        $this->items = [];
    }

    /**
     * @param $item
     * @throws \InvalidArgumentException
     */
    public function add($item)
    {
        $this->throwIfNotValid($item);

        if ($item instanceof Collection) {
            $this->items[$item->getType()] = $item;
        } else {
            $this->items[\get_class($item)] = $item;
        }
    }

    /**
     * @param string $name
     * @return bool|mixed
     */
    public function getItemByName(string $name)
    {
        return $this->items[$name] ?? false;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @param string $type
     * @return bool|mixed
     */
    public function getItemByType(string $type)
    {
        foreach ($this->items as $item) {
            if ($item instanceof $type || ($item instanceof Collection && $item->getType() === $type)) {
                return $item;
            }
        }

        return false;
    }

    /**
     * @param string $type
     * @return bool
     */
    public function allowsType(string $type): bool
    {
        return array_key_exists($type, $this->items);
    }

    public function validate()
    {
        foreach ($this->allowedTypes as $type) {
            if (!isset($this->items[$type])) {
                throw new \InvalidArgumentException('Missing type in sequence: ' . $type);
            }
        }
    }

    /**
     * @param mixed $item
     * @throws \InvalidArgumentException
     */
    protected function throwIfNotValid($item)
    {
        $valid = false;
        foreach ($this->allowedTypes as $type) {
            if ($item instanceof $type || ($item instanceof Collection && $item->getType() === $type)) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            throw new \InvalidArgumentException('Wrong type for collection of ' . implode(', ', $this->allowedTypes));
        }
    }

    /**
     * @param OutputStreamInterface $stream
     * @param string|null $tag
     * @param bool $rootElement
     */
    public function writeToStream(OutputStreamInterface $stream, string $tag = null, bool $rootElement = false)
    {
        $this->validate();

        foreach ($this->allowedTypes as $name => $type) {
            $item = $this->items[$type];
            if ($item instanceof StreamableInterface) {
                $item->writeToStream($stream, $name);
            }
        }
    }
}
