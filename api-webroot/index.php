<?php
require 'lib/flight/flight/Flight.php';
Flight::path( __DIR__ . '/lib' ); # Let the autoloaded know where to find class files.

Flight::route('/', function(){
    echo 'Here be <strike>Dragons</strike>Cheese';
});

Flight::route('/schedule/@from/@to', array( 'Schedule', 'all' ) );
Flight::route('/schedule/@from/@to/@start/@finish', array( 'Schedule', 'search' ) );

Flight::start();
