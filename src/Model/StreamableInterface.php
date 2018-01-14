<?php

declare (strict_types=1);

namespace JDWil\ExcelStream\Excel\Zest;

/**
 * Interface StreamableInterface
 */
interface StreamableInterface
{
    /**
     * @param OutputStreamInterface $stream
     * @param string|null $tag
     */
    public function writeToStream(OutputStreamInterface $stream, string $tag = null);
}
