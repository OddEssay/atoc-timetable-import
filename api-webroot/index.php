<?php
require 'lib/flight/flight/Flight.php';

Flight::route('/', function(){
    echo 'hello world!';
});

Flight::start();
