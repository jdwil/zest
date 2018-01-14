<?php
declare(strict_types=1);

namespace JDWil\Zest\Model;

use JDWil\ExcelStream\Excel\Zest\OutputStreamInterface;
use JDWil\ExcelStream\Excel\Zest\StreamableInterface;

/**
 * Class Collection
 */
class Collection implements StreamableInterface
{
    /**
     * @var string|Choice|Sequence
     */
    private $type;

    /**
     * @var array
     */
    protected $items;

    /**
     * @var int
     */
    protected $minSize;

    /**
     * @var int
     */
    protected $maxSize;

    /**
     * Collection constructor.
     * @param string|Choice|Sequence $type
     * @param int $minSize
     * @param int $maxSize
     */
    public function __construct($type, int $minSize, int $maxSize)
    {
        $this->items = [];
        $this->minSize = $minSize;
        $this->maxSize = $maxSize;
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @param $item
     */
    public function add($item)
    {
        $type = \get_class($item);

        if (\is_string($this->getType())) {
            if ($this->getType() === $type && $this->hasRoom()) {
                $this->items[] = $item;
                return;
            }
        } else {
            if (\get_class($this->getType()) === $type && $this->hasRoom()) {
                $this->items[] = $item;
                return;
            }
        }

        if ($container = $this->findEmptySlot($this->items, $type, $this)) {
            $container->add($item);
            return;
        }

        throw new \InvalidArgumentException('No place to put item');
    }

    /**
     * @param $item
     * @param bool $strict
     * @return int
     * @throws \InvalidArgumentException
     */
    public function remove($item, bool $strict = false): int
    {
        $this->throwIfNotValid($item);

        $removed = 0;
        while ($key = array_search($item, $this->items, $strict)) {
            array_splice($this->items, $key, 1);
            $removed++;
        }

        return $removed;
    }

    /**
     * @param $item
     * @param bool $strict
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function contains($item, bool $strict = true): bool
    {
        $this->throwIfNotValid($item);

        return \in_array($item, $this->items, $strict);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return \count($this->items);
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return \count($this->items) >= $this->minSize && (null === $this->maxSize || \count($this->items) <= $this->maxSize);
    }

    /**
     * @return string|Choice|Sequence
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function hasRoom(): bool
    {
        return -1 === $this->maxSize || $this->count() < $this->maxSize;
    }

    /**
     * @param $item
     * @throws \InvalidArgumentException
     */
    private function throwIfNotValid($item)
    {
        $e = new \InvalidArgumentException('Item must be of type ' . $this->type);
        if (\is_string($this->type) && !$item instanceof $this->type) {
            throw $e;
        } else if (\get_class($this->type) !== \get_class($item)) {
            throw $e;
        }
    }

    /**
     * @param OutputStreamInterface $stream
     * @param string|null $tag
     * @param bool $rootElement
     */
    public function writeToStream(OutputStreamInterface $stream, string $tag = null, bool $rootElement = false)
    {
        foreach ($this->items as $item) {
            if ($item instanceof StreamableInterface) {
                $item->writeToStream($stream, $this->name);
            }
        }
    }

    /**
     * @param array $items
     * @param string $type
     * @param mixed $parent
     * @return bool|mixed
     */
    private function findEmptySlot(array $items, string $type, $parent)
    {
        if ($parent instanceof self &&
            !\is_string($parent->getType()) &&
            $parent->hasRoom() &&
            $parent->getType()->allowsType($type)
        ) {
            $ret = clone $parent->getType();
            $parent->add($ret);

            return $ret;
        }

        foreach ($items as $item) {
            if ($item instanceof self) {
                if ($item->getType() === $type && $item->hasRoom()) {
                    return $item;
                }

                if ($ret = $this->findEmptySlot($item->all(), $type, $item)) {
                    return $ret;
                }
            } else if ($item instanceof Choice) {
                if ($item->allowsType($type)) {
                    if (!$item->selected()) {
                        return $item;
                    }

                    if ($parent instanceof self && $parent->hasRoom()) {
                        $item = clone $item;
                        $parent->add($item);

                        return $item;
                    }
                }

                if ($ret = $this->findEmptySlot([$item->get()], $type, $item)) {
                    return $ret;
                }
            } else if ($item instanceof Sequence) {
                if ($item->allowsType($type)) {
                    if (!$item->getItemByType($type)) {
                        return $item;
                    }

                    if ($parent instanceof self && $parent->hasRoom()) {
                        $item = clone $item;
                        $parent->add($item);

                        return $item;
                    }
                }
                if ($item->allowsType($type) && !$item->getItemByType($type)) {
                    return $item;
                }

                if ($ret = $this->findEmptySlot($item->all(), $type, $item)) {
                    return $ret;
                }
            }
        }

        return false;
    }
}
