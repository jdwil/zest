<?php

declare (strict_types=1);

namespace JDWil\ExcelStream\Excel\Zest;

/**
 * Interface OutputStreamInterface
 */
interface OutputStreamInterface
{
    /**
     * @param string $data
     */
    public function write(string $data);

    /**
     * @param string $data
     */
    public function writeLine(string $data);
}
