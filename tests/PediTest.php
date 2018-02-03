<?php

use PHPUnit\Framework\TestCase;
use EDI\Pedi;

require "EDI/Pedi.php";

final class PediTest extends TestCase
{

    public function testPediLoadsAnEDIFile()
    {
        $pedi = new Pedi();
        $this->assertInstanceOf(Pedi::class, $pedi);
        $pedi->read(__DIR__ . '/test_files/cws23.x12');
        $this->assertInternalType("array", $pedi->documents());
    }

    /**
     * @expectedException Exception
     */
    public function testPediHandlesLoadingBadFile()
    {
        $this->expectException(Exception::class);
        $pedi = new Pedi();
        $pedi->read(__DIR__ . '/test_files/x12_test_array.json');
    }

     /**
     * @expectedException ArgumentCountError
     */
    public function testThrowsErrorOnNoFile() 
    {
        $this->expectException(ArgumentCountError::class);
        $pedi = new Pedi();
        $pedi->read();
    }

    public function testItLoadsTheAppropriateX12Schema()
    {
        $pedi = new Pedi(['edi_type' => 'EDIFACT', 'validate' => true]);
        $pedi->read(__DIR__ . '/test_files/bad_edi_two_beg_file.x12');
        $this->assertInternalType("array", $pedi->documents());
    }
   
}