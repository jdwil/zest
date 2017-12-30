<?php

require_once dirname(__FILE__) . '/vendor/autoload.php';

$output = new \JDWil\Zest\Excel\JDWil\Zest\Excel\Zest\FileOutputStream('./worksheet.xml');
$worksheet = new \Ooxml\Spreadsheetml\Main\worksheet();
try {
    $worksheet->writeToStream($output);
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
}
