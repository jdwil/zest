<?php
declare(strict_types=1);

namespace JDWil\Zest\Model;

/**
 * Class Collection
 */
class Collection
{
    const TYPE_NORMAL = 0;
    const TYPE_CHOICE = 1;

    /**
     * @var int
     */
    private $collectionType;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string[]
     */
    private $types;

    /**
     * @var array
     */
    private $items;

    /**
     * @var int
     */
    private $minSize;

    /**
     * @var int|null
     */
    private $maxSize;

    private function __construct() {}

    /**
     * @param string $type
     * @param int $minSize
     * @param int|null $maxSize
     * @return Collection
     */
    public static function of(string $type, int $minSize = 0, int $maxSize = null): Collection
    {
        $ret = new static;
        $ret->collectionType = self::TYPE_NORMAL;
        $ret->items = [];
        $ret->type = $type;
        $ret->minSize = $minSize;
        $ret->maxSize = $maxSize;

        return $ret;
    }

    /**
     * @param array $types
     * @param int $minSize
     * @param int|null $maxSize
     * @return Collection
     */
    public static function ofChoices(array $types, int $minSize = 0, int $maxSize = null): Collection
    {
        $ret = new static;
        $ret->collectionType = self::TYPE_CHOICE;
        $ret->items = [];
        $ret->types = $types;
        $ret->minSize = $minSize;
        $ret->maxSize = $maxSize;

        return $ret;
    }

    /**
     * @param $item
     * @throws \InvalidArgumentException
     */
    public function add($item)
    {
        $this->throwIfNotValid($item);

        $this->items[] = $item;
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
    private function throwIfNotValid($item)
    {
        if (self::TYPE_NORMAL === $this->collectionType && !$item instanceof $this->type) {
            throw new \InvalidArgumentException('Wrong type for collection of ' . $this->type);
        }

        if (self::TYPE_CHOICE === $this->collectionType) {
            $valid = false;
            foreach ($this->types as $type) {
                if ($item instanceof $type) {
                    $valid = true;
                    break;
                }
            }

            if (!$valid) {
                throw new \InvalidArgumentException('Wrong type for collection of ' . implode(', ', $this->types));
            }
        }
    }
}
