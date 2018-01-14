<?php
declare(strict_types=1);

namespace JDWil\Zest\Util;

use JDWil\PhpGenny\Builder\Node\AbstractNode;
use JDWil\PhpGenny\Type\Method;

class CollectionLogic
{
    public static function addToCollectionInChoice(Method $m, AbstractNode $choice, string $name, string $type)
    {
        $m->getBody()
            ->if()
        ;
    }
}
