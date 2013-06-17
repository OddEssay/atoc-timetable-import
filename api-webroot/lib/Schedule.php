<?php
class Schedule {
    public static function all($from,$to) {
        echo "From: " . $from . " To: " . $to;
    }
    public static function search($from,$to,$start,$finish){
        echo "From: " . $from . " To: " . $to . ' Between ' . $start . ' and ' . $finish;
    }
}