<?php
/**
 * Update Station Names
 * Q: Doesn't the "TIPLOC" infomation include an English name?
 * A: Yes, but this isn't the label the public normally see, which comes from the Master Station Names file.
 * File Definition is in: http://data.atoc.org/sites/all/themes/atoc/files/rsps5041%20-%20Timetable%20Data.pdf
 */

$startTimestamp = date('U');
define('DEBUG',false); // Turn debug output on and off here.
define('FIRSTRUN',true); // Do additional setup for first run.

$c = 0;

MongoCursor::$timeout = -1; // Stop timeouts (Particularly with remove on big result sets)

$mongo = new MongoClient();
// select a database
$db = $mongo->trains;

$locations = $db->locations;

if(FIRSTRUN === true){
	// N/A	
}

$handle = @fopen("/vagrant/TTISF036.MSN", "r");
if ($handle) {

	$firstLine = fgets($handle, 4096); # We can't identify the first line by prefix, so just process once to get it out of the way.
    
    while (($line = fgets($handle, 4096)) !== false) {

    	++$c;
    	if( $c % 100 === 0 ) { echo "Tick: ".number_format($c)."\n"; }

    	$type = substr($line, 0, 1); // First Two Characters Are the type code

   		switch($type){
   			/*
   			 * 3.2.7.2 Station Details
   			 * Name 					Start 	Length 	Description
			 * Record Type  			0 		1 		“A”
			 * Spaces 					1 		4 		Spaces
			 * Station Name 			5 		30 		Station Name
			 * CATE Type 				35 		1 		Interchange Status. Values:
			 * 												0 Not an interchange Point
			 * 												1 Small Interchange Point
			 * 												2 Medium Interchange Point
			 * 												3 Large Interchange Point
			 * 												9 This is a subsidiary TIPLOC at a station which has more than one TIPLOC. Stations which have more than one TIPLOC always have the same principal 3-Alpha Code.
			 *													This field enables a Timetables enquiry system to give some precedence for changing at large interchange 
			 * 													points ahead of medium interchange points ahead of  small interchange points.
			 * TIPLOC code 				36 		7 		Location code as held in the CIF data
			 * Subsidiary 3-Alpha code  43 		3 		Where a station has more than one TIPLOC e.g.Tamworth, this is set to the 3-Alpha code that is not in 
			 * 											the field below. Normally this is a repeat of the 3-Alpha code
			 * Spaces 					46 		3 		Spaces
			 * 3-Alpha Code 			49		3 		Principal 3-Alpha Code of Station. Part of location code for the manual trains CIF data
			 * Easting 					52 		5 		Easting in units of 100m. Stations too far south (Channel Islands) or too far north (Orkneys) or too far 
			 * 												west (west of Carrick on Shannon) have both their Easting and Northing set to 00000. The most westerly 
			 * 												station in range, Carrick on Shannon, has value 10000. The most easterly station, Amsterdam, has value 18690.
			 * Estimated 				57 		1 		“E” means estimated coordinates, space otherwise
			 * Northing 				58 		5 		Northing in units of 100m. Stations too far south (Channel Islands) or too far north (Orkneys) or too far 
			 * 												west (west of Carrick on Shannon) have both their Easting and Northing set to 00000. The most 
			 * 												southerly station in range, Lizard (Bus), has value 60126. The most northerly station in range, Scrabster, has value 69703.
			 * Change Time 				63 		2 		Change time in minutes
			 * Footnote 				65 		2 		CATE footnote. This data is historic, is not maintained and should be ignored.
			 * Spaces 					67 		11 Spaces
			 */
   			case 'A': {
   				$detail = array();
   				$geo = array();

   				$detail['name'] = trim( substr($line, 5, 30) );
   				$detail['cateType'] = substr($line, 35, 1);
   				$detail['tiploc'] = trim( substr($line, 36, 7) );
   				
   				$geo['easting'] = substr($line, 52, 5);
   				$geo['estimated'] = substr($line, 57, 1);
   				$geo['northing'] = substr($line, 58, 5);
   				
   				$detail['geo'] = $geo;

   				$detail['changeTime'] = substr($line, 63, 2);

   				$locations->update( [ 'tiploc' => $detail['tiploc'] ], [ '$set' => $detail ] );
   			
   				unset($geo);
   				unset($detail);

   				break;
   			} 
   			default: {
   				if(DEBUG === true) { echo "Uncaught Line: ".$line; }
   				++$miss;
   			}
   		}

    }
    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail\n";
    }
    fclose($handle);
}

/*
 * Helper Memory report from :
 */
function readable_memory_usage() { 
	$mem_usage = memory_get_usage(true); 

	if ($mem_usage < 1024) 
	    $string = $mem_usage." bytes"; 
	elseif ($mem_usage < 1048576) 
	    $string = round($mem_usage/1024,2)." kilobytes"; 
	else 
	    $string = round($mem_usage/1048576,2)." megabytes"; 
	    
	return $string; 
}
function readable_peak_memory_usage() { 
	$mem_usage = memory_get_peak_usage(true); 

	if ($mem_usage < 1024) 
	    $string = $mem_usage." bytes"; 
	elseif ($mem_usage < 1048576) 
	    $string = round($mem_usage/1024,2)." kilobytes"; 
	else 
	    $string = round($mem_usage/1048576,2)." megabytes"; 
	    
	return $string; 
} 
echo "\nTotal time: ".( ( date('U') - $startTimestamp ) / 60 )." mins.\n";
echo "Total Lines: " . $c. "\n";
echo "Missed: Miss: " . $miss . "\n"; 
echo "Memory Use: " . readable_memory_usage() . "\n";
echo "Peak Memory: " . readable_peak_memory_usage() . "\n";
