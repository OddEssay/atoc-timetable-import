<?php
require 'lib/Stations.php';

class StationsTest extends PHPUnit_Framework_TestCase {
    function testFindByCRS() {
        $station = Stations::findByCRS('LVC');
        $this->assertEquals('Liverpool Central',$station['name']);
    }
    function testFindNearestByGeo(){
        $station = Stations:findNearestByGeo($lat,$lng);
        $this->assertEquals('Liverpool Central',$station['name']);
        $this->assertEquals('moo','true')
    }
}