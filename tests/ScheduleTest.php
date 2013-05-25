<?php
class ScheduleTest extends PHPUnit_Framework_TestCase {
    function testHelloWorld() {
        $this->assertEquals( true, 1);
        return 1;
    }
    
    /**
     * @depends testHelloWorld
     */
    function testTwo($number){
        $number++;
        $this->assertEquals(2,$number);
    }
}