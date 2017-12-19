<?php
declare(strict_types=1);

namespace JDWil\Zest\Element\Traits;

use JDWil\Zest\Facet\AbstractFacet;

/**
 * Trait FacetedTrait
 */
trait FacetedTrait
{
    /**
     * @var AbstractFacet[]
     */
    protected $facets;

    /**
     * @return array
     */
    public function getFacets(): array
    {
        return $this->facets;
    }
}
