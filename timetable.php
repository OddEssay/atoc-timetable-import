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
	die( json_encode($errors) );
}

$fromLocation = $locations->find(['crs' => $from])->getNext();
$toLocation = $locations->findOne(['crs' => $to]);

//echo "Searching Train From: ".$fromLocation['tpsDescription'] . "(" . $fromLocation['name'] . ") To ".$toLocation['tpsDescription']. "(" . $toLocation['name'] . ") \n";

// Example: db.timetables.find( { $and: [ { "stops.location":"STMCHLS" } ], "stops.location": "LVRPLCH" } );
// TODO: Better way to structure query?

$query = array( '$and' => array( array( "stops.location" => $fromLocation['tiploc'] ) ), "stops.location" => $toLocation['tiploc'] );

$result = $timetables->find($query);

foreach($result as $document){
	$docArray[] = $document;
}

$docArray['from'] = $fromLocation;
$docArray['to'] = $toLocation;

header('Content-Type: application/json');
echo json_encode($docArray);
