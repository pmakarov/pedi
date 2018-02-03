<?php

use PHPUnit\Framework\TestCase;
use EDI\X12\Parser;

require_once "EDI/X12/Parser.php";

final class ParserTest extends TestCase
{

    /**
     * @expectedException ArgumentCountError
     */
    public function testParserFailsWithNoResource()
    {
        $this->expectException(ArgumentCountError::class);
        $parser = Parser::parse();

    }

    /**
     * @expectedException Exception
     */
    public function testParserFailsWithEmptyResource()
    {
        $this->expectException(Exception::class);
        $parser = Parser::parse("");

    }

    /**
     * @expectedException Exception
     */
    public function testParserRejectsEDIWithNoISA()
    {
        $this->expectException(Exception::class);
        $test_edi = file_get_contents(__DIR__ . '/test_files/noISA.x12');
        $doc = Parser::parse($test_edi);
    }

    public function testParserReturnsArray()
    {
        $test_edi = file_get_contents(__DIR__ . '/test_files/cws23.x12');
        $doc = Parser::parse($test_edi);
        $this->assertInternalType("array", $doc);
    }

    public function testParserHandlesSubElementSeparator()
    {
        $test_edi = file_get_contents(__DIR__ . '/test_files/edi_with_separator.x12');
        $lines = explode("\n", $test_edi);
        $skipped_content = implode("\n", array_slice($lines, 2));
        //print_r($skipped_content);
        $this->assertContains('>', $skipped_content);

        $doc = Parser::parse($test_edi);
        $this->assertContains($doc[0]->segments[0][0], 'ISA');
        $this->assertContains($doc[0]->segments[0][16], '>');
        //$this->assertArraySubset($doc[0]->segments[17][1], $doc[0]->segments[17][1]);
        
        //TODO: assert that we see the result of subelement separation.
    
    }
}