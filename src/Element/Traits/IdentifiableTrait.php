<?php
declare(strict_types=1);

namespace JDWil\Zest\Element\Traits;

/**
 * Trait IdentifiableTrait
 */
trait IdentifiableTrait
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
}
