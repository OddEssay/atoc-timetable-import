<?php

define(DEBUG,true); // Turn debug output on and off here.

// Based on the specification at http://www.atoc.org/clientfiles/File/RSPS5004%20v27.pdf

$handle = @fopen("/tmp/inputfile.txt", "r");
if ($handle) {
    while (($line = fgets($handle, 4096)) !== false) {
        echo $line;

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
        		$header = array();
        		$header['recordIdentity'] = substr($line, 0, 2); // Will always be HD
        		$header['fileMainframeIdentity'] = trim( substr($line, 2, 2) );
        		$header['dateOfExtract'] = trim( substr($line, 4, 6) );
        		$header['timeOfExtract'] = trim( substr($line, 10, 4) );
        		$header['currentFileRef'] = trim( substr($line, 14, 7) );
        		$header['lastFileRef'] = trim( substr($line, 21, 7) );
        		$header['bleedOff'] = trim( substr($line, 28, 1) );
        		$header['version'] = trim( substr($line, 29, 1) );
        		$header['userExtractStartDate'] = trim( substr($line, 30, 6) );
        		$header['userExtractEndDate']	= trim( substr($line, 36, 6) );
        		// We don't bother with the spare data.

        		if(DEBUG){ echo "\n\nHD: \n"; print_r($header); }

        		break;
        	}
        	// TIPLOC
        	case 'TI': {
        		break;
        	}
        	case 'TA': {

        	}
        	case 'TD': {

        	}
        	// Association Records
        	case 'AA': {

        	}
        	// Train Scheduals
        	case 'BS': { # a basic schedule record

        	}
        	case 'BX': { # a basic schedule extra details record

        	}
        	case 'TN': { # train specific note records, if present

        	}
        	case 'LO': { # an origin location record 

        	}
        	case 'LI': { # all intermediate location records in journey sequence

        	}
        	case 'CR': { #preceded by a Change en Route, if present, for the location

        	}
        	case 'LT': { # terminating location record

        	}
        	case 'LN': { # location specific note records (These may follow any LO, LI or LT records)

        	}

        }

    }
    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail\n";
    }
    fclose($handle);
}
?>