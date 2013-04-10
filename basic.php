<?php
$startTimestamp = date('U');
define('DEBUG',false); // Turn debug output on and off here.

$c  = 0;
$ta = 0;
$td = 0;
$aa = 0;

$mongo = new MongoClient();
// select a database
$db = $mongo->trains;

// select a collection (analogous to a relational database's table)
$timetables = $db->timetables;
$timetables->ensureIndex("stops.location");

$locations = $db->locations;
$locations->ensureIndex("TIPLOC");

// Based on the specification at http://www.atoc.org/clientfiles/File/RSPS5004%20v27.pdf

$handle = @fopen("/vagrant/TTISF036.MCA", "r");
if ($handle) {
    while (($line = fgets($handle, 4096)) !== false) {
    	++$c;
    	if( $c % 1000 === 0 ) { echo "Tick: ".number_format($c)."\n"; }
        $type = substr($line, 0, 2); // First Two Characters Are the type code
        switch($type){
        	/**
        	 * 4.1 Header Record (HD)
        	 * 
			 * Field Name 				Start 	Size Format Comment 
			 * Record Identity			0 		2    A 		value HD 
			 * File Mainframe Identity 	2 		2 	 A 		See note (1)
			 * Date of Extract 			4 		6	 N 		Format ddmmyy. See note (2)
			 * Time of Extract 			10 		4 	 N 		Format hhmm. 24-hour clock
			 * Current-File-Ref 		14 		7 	 A 		Unique file reference. See note (1)
			 * Last-File-Ref 			21 		7 	 A 		Last file generated ref. See note (1)
			 * Bleed-off/Update Ind 	28 		1 	 A 		U = update extract F = full extract
			 * Version					29 		1 	 A 		Version of CIF software, incremented for each significant change.
			 * User Extract Start Date 	30 		6 	 N 		Format ddmmyy. Today's date if user time window is number of days. See note (2)
			 * User Extract End Date 	36		6 	 N 		Format ddmmyy. See note (2)
			 * Spare 					42		20 	 A
        	 */
        	case 'HD': {

        		if(DEBUG){ echo "\n\nRaw HD Line: \n"; print_r($line); }

        		$header = array();
        		$header['recordIdentity'] = substr($line, 0, 2); // Will always be HD
        		$header['fileMainframeIdentity'] = (string)trim( substr($line, 2, 2) );
        		$header['dateOfExtract'] = trim( substr($line, 4, 6) );
        		$header['timeOfExtract'] = trim( substr($line, 10, 4) );
        		$header['currentFileRef'] = (string)trim( substr($line, 14, 7) );
        		$header['lastFileRef'] = (string)trim( substr($line, 21, 7) );
        		$header['bleedOff'] = (string)trim( substr($line, 28, 1) );
        		$header['version'] = trim( substr($line, 29, 1) );
        		$header['userExtractStartDate'] = trim( substr($line, 30, 6) );
        		$header['userExtractEndDate']	= trim( substr($line, 36, 6) );
        		// We don't bother with the spare data.

        		if(DEBUG){ echo "\n\nResult of HD: \n"; print_r($header); }

        		break;
        	}
        	// TIPLOC
        	/**
        	 * 4.11 TIPLOC Insert
			 * Field 					Start 	Size 	Format 	Comment
			 * Record Identity 			0 		2 		A 		value TI
			 * TIPLOC code 				2 		7 		A
			 * Capitals Identification 	9 		2 		N 		Defines capitalisation of TIPLOC
			 * Nalco 					11 		6 		N 		National Location code
			 * NLC check character 		17 		1 		A
			 * TPS Description 			18 		26 		A 		"English" description of location
			 * Stanox 					44 		5 		N 		TOPS location code
			 * PO MCP Code 				49 		4 		N 		Post Office location code. (Not used)
			 * CRS Code 				53 		3 		A
			 * 16 character description 56 		16 		A 		description used in CAPRI 
			 * Spare 					72 		8 		A
			 */
        	case 'TI': {
        		$location = array();
        		$location['recordIdentity'] = substr($line, 0, 2);
        		$location['tiploc'] = substr($line, 2, 7);
        		$location['capitalsIdentification'] = substr($line, 9, 2);
        		$location['nalco']	= trim( substr($line, 11, 6) );
        		// Skipped
        		$location['tpsDescription'] = trim( substr($line, 18, 26) );
        		// Skipped
        		$location['crs'] = substr($line, 53, 3);
        		$locations->insert($location);
        		unset($location);
        		break;
        	}
        	case 'TA': {
        		++$ta;
        		break;
        	}
        	case 'TD': {
        		++$td;
        		break;
        	}
        	// Association Records
        	case 'AA': {
        		++$aa;
        		break;
        	}
        	// Train Schedule
        	/**
        	 * 4.2 Basic Schedule
			 * Field Name 				 Start		Size Format Comment 
			 * Record Identity  		 0			2 	 A 		value BS
			 * Transaction Type 		 2 			1 	 A 		N = new, D = delete, R = Revise.
			 * Train UID 				 3 			6 	 A 		UNIQUE-ID
			 * Date Runs From   		 9 			6 	 N 		Format yymmdd.
			 * Date Runs To 			 15 		6 	 N 		Format yymmdd or '999999' if open-ended.
			 * Days Run 				 22 		7 	 N 		DAYS Note: 7-character binary field with each character set to 1 (train runs) or 0 (train does not run). Position 1 = Monday, 2 = Tuesday, etc. through to 7 = Sunday
			 * Bank Holiday Running 	 29 		1 	 A 		BHX – See Appendix A
			 * Train Status 			 30 		1 	 A 		STATUS - See Appendix A
			 * Train Category 			 31 		2 	 A 		CATEGORY - See Appendix A
			 * Train Identity 			 33 		4 	 A 		TRAIN-IDENTITY. Format nann
			 * Headcode 				 37 		4 	 A 		HEADCODE - See Appendix A
			 * Course Indicator 		 41 		1 	 A 		COURSE-IND. Not used. Value „1‟ (one)
			 * Train Service Code 		 42 		8 	 A 		SERVICE-CODE – See note (4) 
			 * Portion Id 				 50 		1 	 A 		BUSSEC - See Appendix A and note (9)
			 * Power Type 				 51 		3 	 A 		POWER-TYPE - See Appendix A
			 * Timing Load 				 54 		4 	 A 		TIMING-LOAD - See Appendix A
			 * Speed 					 58 		3 	 A 		SPEED – See note (5) 
			 * Operating Characteristics 61 		6 	 A 		OPER-CHARS - 6 x 1 - See Appendix A
			 * Train Class 				 67			1 	 A 		CLASS - See Appendix A
			 * Sleepers 				 68			1 	 A 		SLEEPERS - See Appendix A
			 * Reservations 			 69 		1 	 A 		RESERVATIONS - See Appendix A
			 * Connection Indicator 	 70			1 	 A 		CONNECT-IND. Not used. 
			 * Catering Code 			 71 		4 	 A 		CATERING - 4 x 1 - See Appendix A
			 * Service Branding 		 75 		4 	 A 		SERVICE-BRAND - 4 x 1 - See Appendix A
			 * Spare 					 79 		1 	 A
			 * STP Indicator 			 80 		1 	 A 		space - if non overlay user,
			 *														C - STP Cancellation of Permanent schedule, 
			 * 														N - New STP schedule (not an overlay), 
			 * 														O - STP overlay of Permanent schedule,
			 * 														P - Permanent.
        	 */
        	case 'BS': { # a basic schedule record
        		if(DEBUG){ echo "\n\nRaw BS Line: \n"; print_r($line); }

        		$schedule = array();
        		$schedule['stops'] = array();

        		$schedule['recordIdentity'] = substr($line, 0, 2); // Will always be HD
        		$schedule['transactionType'] = trim( substr($line, 2, 1) );
        		$schedule['trainUID'] = (string)trim( substr($line, 3, 6) );
        		$schedule['dateRunsFrom'] = trim( substr( $line, 9, 6) );
        		$schedule['dateRunsTo'] = trim( substr( $line, 15, 6) );
        		$schedule['daysRun'] = (string)trim( substr( $line, 22, 7) ); // Docs say this is a number, but to maintain 0s we cast to string.
        		$schedule['bankHolidayRunning'] = (string)trim( substr( $line, 29, 1) );
        		$schedule['trainStatus'] = trim( substr( $line, 30, 1) );
        		$schedule['trainCategory'] = trim( substr( $line, 31, 2) );
        		$schedule['trainIndicator'] = trim( substr($line, 33,4) );
        		$schedule['headcode'] = trim( substr($line, 37, 4) );
        		$schedule['courseIndicator'] = trim( substr($line, 41, 1) );
        		$schedule['trainServiceCode'] = trim( substr($line, 42, 8) );
        		// Skipped
        		$schedule['serviceBranding'] = trim( substr($line, 75, 4) ); 
        		// We don't bother with the spare data.

        		if(DEBUG){ echo "\n\nResult of BS: \n"; print_r($schedule); }

        		break;
        	}
        	case 'BX': { # a basic schedule extra details record
        		# Ignored in this version.
        		break;
        	}
        	case 'TN': { # train specific note records, if present
        		break;
        	}
        	/**
        	 * 4.4 Origin Location
			 * Field Name 			 Start 	Size 	Format 	Comment
			 * Record Identity 		 0 		2 		A 		value LO
			 * Location  			 2 		8 		A 		LOCATION, (TIPLOC + Suffix)
			 * Scheduled Departure   10 	5 		A 		SCHED-DEP-TIME. Format hhmm 24-hr clock  + optional „H‟ = half-minute
			 * Public Departure 	 15 	4 		N 		PUBLIC-DEP-TIME. Format hhmm 24-hr clock
			 * Platform 			 19 	3 		A 		PLATFORM. See note (1) 
			 * Line 				 22 	3 		A 		LINE. See note (2)
			 * Engineering Allowance 25 	2 		A 		ENG-ALLOW – See Appendix A
			 * Pathing Allowance  	 27 	2 		A 		PATH-TIME– See Appendix A
			 * Activity 			 29 	12 		A 		ACTIVITY - 6 x 2– See Appendix A and note (3)
			 * Performance Allowance 41 	2 		A 		PERFORM-ALLOW – See Appendix A
			 * Spare 				 43 	37 		A
			 */
        	case 'LO': { # an origin location record
        		$stop = array();
        		$stop['recordIdentity'] = trim( substr($line, 0, 2) );
        		$stop['location'] = trim( substr($line, 2, 8) );
        		$stop['crs'] = substr($line, 2, 3);
        		$stop['scheduledDeparture'] = trim( substr($line, 11, 4) ); // We only capture the first 4 parts of scheduledDeparture, and convert H to .5 seconds in next line.
        		if(substr($line, 10, 1) === 'H' ){ $stop['scheduledDeparture'] = $stop['scheduledDeparture'] + 0.5; }
        		$stop['publicDeparture'] = trim( substr($line, 15, 4) );
        		$stop['platform'] = trim( substr($line, 22, 3) );

        		$schedule['stops'][] = $stop;
        		break;
        	}
        	/**
        	 * 4.5 Intermediate Location
			 * Field Name 				 Start  Size 	Format 	Comment
			 * Record Identity 			 0		2 		A 		value LI
			 * Location  				 2 		8 		A 		LOCATION, (TIPLOC + Suffix)
			 * Scheduled Arrival 		 10 	5 		A 		SCHED-ARR-TIME Format hhmm 24-hr clock + optional „H‟ = half-minute
			 * Scheduled Departure 		 15 	5 		A 		SCHED-DEP-TIME Format hhmm 24-hr clock + optional „H‟ = half-minute
			 * Scheduled Pass 			 20 	5 		A 		SCHED-PASS Format hhmm 24-hr clock + optional „H‟ = half-minute
			 * Public Arrival 			 24 	4 		N 		PUBLIC-ARR-TIME. Format hhmm 24-hr clock
			 * Public Departure			 28 	4 		N 		PUBLIC-DEP-TIME. Format hhmm 24-hr clock
			 * Platform 				 32 	3 		A 		PLATFORM – See note (1)
			 * Line 					 35 	3 		A 		LINE– See note (2)
			 * Path 					 38 	3 		A 		PATH– See note (3)
			 * Activity 				 41 	12 		A 		ACTIVITY - 6x2. See Appendix A and note (4)
			 * Engineering Allowance 	 53 	2 		A 		ENG-ALLOW – See Appendix A
			 * Pathing Allowance 		 55 	2 		A 		PATH-TIME – See Appendix A
			 * Performance Allowance 	 57 	2 		A 		PERFORM-ALLOW – See Appendix A
			 * Spare 					 59 	20 		A
			 */
        	case 'LI': { # all intermediate location records in journey sequence
        		$stop = array();
        		$stop['recordIdentity'] = trim( substr($line, 0, 2) );
        		$stop['location'] = trim( substr($line, 2, 8) );
        		$stop['crs'] = substr($line, 2, 3);
        		$stop['scheduledArrival'] = trim( substr($line, 11, 4) ); // We only capture the last 4 parts of scheduledArrival, and convert H to .5 seconds in next line.
        		if(substr($line, 10, 1) === 'H' ){ $stop['scheduledArrival'] = $stop['scheduledArrival'] + 0.5; }
        		$stop['scheduledDeparture'] = trim( substr($line, 16, 4) ); // We only capture the last 4 parts of scheduledDeparture, and convert H to .5 seconds in next line.
        		if(substr($line, 15, 1) === 'H' ){ $stop['scheduledDeparture'] = $stop['scheduledDeparture'] + 0.5; }
        		$stop['publicArrival'] = trim( substr($line, 24, 4) );
        		$stop['publicDeparture'] = trim( substr($line, 28, 4) );
        		$stop['platform'] = trim( substr($line, 22, 3) );

        		$schedule['stops'][] = $stop;
        		break;
        	}
        	case 'CR': { #preceded by a Change en Route, if present, for the location

        	}
        	case 'LT': { # terminating location record
        		$timetables->insert($schedule); 
        		unset($schedule); # Not unsetting the $schedule results in MongoDB reusing _id and getting dup index errors.
        		break;
        	}
        	case 'LN': { # location specific note records (These may follow any LO, LI or LT records)

        	}

        	default: {
        		if(DEBUG){ echo "Uncaught line: ".$line; }
        	}

        }

    }
    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail\n";
    }
    fclose($handle);
}

echo "\nTotal time: ".( ( date('U') - $startTimestamp ) / 60 )." mins.\n";
echo "Missed: TA: " . $ta . " TD: " . $td . " AA: " . $aa . "\n";  
?>