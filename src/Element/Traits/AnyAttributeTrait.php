<?php
declare(strict_types=1);

namespace JDWil\Zest\Element\Traits;

/**
 * Trait AnyAttributeTrait
 */
trait AnyAttributeTrait
{
    /**
     * @var array
     */
    protected $otherAttributes = [];

    /**
     * @return array
     */
    public function getOtherAttributes(): array
    {
        return $this->otherAttributes;
    }
}
