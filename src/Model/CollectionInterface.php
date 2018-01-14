<?php
declare(strict_types=1);

namespace JDWil\Zest\Model;

/**
 * Interface CollectionInterface
 */
interface CollectionInterface
{
    /**
     * @param mixed $item
     */
    public function add($item);

    /**
     * @param mixed $item
     * @param bool $strict
     * @return int
     */
    public function remove($item, bool $strict = false): int;

    /**
     * @param mixed $item
     * @param bool $strict
     * @return bool
     */
    public function contains($item, bool $strict = true): bool;

    /**
     * @return int
     */
    public function count(): int;

    /**
     * @return bool
     */
    public function isValid(): bool;
}
