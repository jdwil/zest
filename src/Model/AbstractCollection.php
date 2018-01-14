<?php
declare(strict_types=1);

namespace JDWil\Zest\Model;

/**
 * Class AbstractCollection
 */
abstract class AbstractCollection implements CollectionInterface
{
    /**
     * @var string
     */
    protected $name;

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
     * AbstractCollection constructor.
     * @param int $minSize
     * @param int $maxSize
     */
    public function __construct(int $minSize, int $maxSize)
    {
        $this->items = [];
        $this->minSize = $minSize;
        $this->maxSize = $maxSize;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return AbstractCollection|false
     */
    public function getCollectionByName(string $name)
    {
        foreach ($this->items as $item) {
            if ($item instanceof self && $item->getName() === $name) {
                return $item;
            }
        }

        return false;
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
     * @param $item
     * @throws \InvalidArgumentException
     */
    abstract protected function throwIfNotValid($item);
}
