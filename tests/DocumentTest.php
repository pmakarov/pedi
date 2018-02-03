<?php

use PHPUnit\Framework\TestCase;
use EDI\X12\Document;

final class DocumentTest extends TestCase
{

    public function testConstructor()
    {
        $test_segment = [1,2,3,4];
        $doc = new EDI\X12\Document($test_segment);
        $this->assertInternalType("array", $test_segment);
    }

    public function testDocumentToSring() 
    {
        $test_segment = json_decode(file_get_contents(__DIR__ . '/test_files/x12_test_array.json'), true);
        $doc = new EDI\X12\Document($test_segment);
        $this->assertInternalType("string", $doc->__toString());
    }
}