<?php

require 'EDI/Pedi.php';

use EDI\Pedi;

echo "<pre>";
// TODO: show you can Instantiate, Load, Parse, Read, and Validate for MVP

// 1. intantiate pedi object - give it some params
$pedi = new Pedi(array(
    'validate' => true,
    'schema' => '850_3050',
    'debug' => false, // set true to see debug output of edi parse
));

// 2. read an EDI document
$pedi->read(__DIR__ . '/tests/test_files/cws23.x12');

// 3. find elements in path:
$edi = $pedi->find("ST/AMT");
print count($edi) . " result(s) returned...\n";

// sample print to ouput edi elements returned
for ($i = 0; $i < count($edi); $i++) {
    print_r($edi[$i]->data);    
}

// 5. output edi document as a string
$pedi->export();

echo "</pre>";

