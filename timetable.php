<?php
$mongo = new MongoClient();
// select a database
$db = $mongo->trains;

// select a collection (analogous to a relational database's table)
$timetables = $db->timetables;
$locations = $db->locations;

$_GET['from'] = 'STM';
$_GET['to'] = 'LVC';

if($_GET['from']) {
	preg_match('/^[A-Z]{0,3}/', strtoupper($_GET['from']), $fromResult);
	$from = $fromResult[0];
}
if($_GET['to']) {
	preg_match('/^[A-Z]{0,3}/', strtoupper($_GET['to']), $toResult);
	$to = $toResult[0];
}

$fromLocation = $locations->find(['crs' => $from])->getNext();
$toLocation = $locations->findOne(['crs' => $to]);
print_r($fromLocation);
echo "Searching Train From: ".$fromLocation['tpsDescription']." To ".$toLocation['tpsDescription']."\n";

// Example: db.timetables.find( { $and: [ { "stops.location":"STMCHLS" } ], "stops.location": "LVRPLCH" } );
// TODO: Better way to structure query?

$query = array( '$and' => array( array( "stops.location" => $fromLocation['tiploc'] ) ), "stops.location" => $toLocation['tiploc'] );

$result = $timetables->find($query);

foreach($result as $document){
	$docArray[] = $document;
}

echo json_encode($docArray);
