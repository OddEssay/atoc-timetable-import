<?php
$mongo = new MongoClient();
// select a database
$db = $mongo->trains;

// select a collection (analogous to a relational database's table)
$timetables = $db->timetables;
$locations = $db->locations;

$errors = array();

if( isset( $_GET['from'] ) ) {
	preg_match('/^[A-Z]{0,3}/', strtoupper($_GET['from']), $fromResult);
	$from = $fromResult[0];
} else {
	$errors[] = '"from" not set.';
}
if( isset( $_GET['to'] ) )  {
	preg_match('/^[A-Z]{0,3}/', strtoupper($_GET['to']), $toResult);
	$to = $toResult[0];
} else {
	$errors[] = '"to" not set.';
}

if( count($errors) )
{
	header('Content-Type: application/json');
	http_response_code(400); # HTTP 400 = 'Bad Request'
	die( json_encode($errors) );
}

$fromLocation = $locations->findOne(['crs' => $from]);
$toLocation = $locations->findOne(['crs' => $to]);

//echo "Searching Train From: ".$fromLocation['tpsDescription'] . "(" . $fromLocation['name'] . ") To ".$toLocation['tpsDescription']. "(" . $toLocation['name'] . ") \n";

// Example:  "stops.tiploc": { $all: ["STMCHLS","LVRPLCH"] } }
// TODO: Better way to structure query?

$query = array( "stops.tiploc" =>  array( '$all' => array( $fromLocation['tiploc'],$toLocation['tiploc'] ) ) );

$result = $timetables->find($query);

$docArray['from'] = $fromLocation;
$docArray['to'] = $toLocation;
$docArray['schedule'] = array();
foreach($result as $document){
	$docArray['schedule'][] = $document;
}


header('Content-Type: application/json');
echo json_encode($docArray);